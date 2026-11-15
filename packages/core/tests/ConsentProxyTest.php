<?php

declare(strict_types=1);

namespace Veribenim\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Veribenim\Proxy\ClientIp;
use Veribenim\Proxy\ConsentProxy;
use Veribenim\Proxy\ConsentProxyRequest;
use Veribenim\Tests\Support\FakeProxyTransport;
use Veribenim\VeribenimConfig;

class ConsentProxyTest extends TestCase
{
    private const TOKEN = 'abcdEFGH1234567890abcdEFGH123456';
    private const KEY = 'vbpk_SECRETsecretSECRETsecretSECRETsecret12';

    private FakeProxyTransport $transport;
    private ConsentProxy $proxy;
    private string $cookie;

    protected function setUp(): void
    {
        $this->cookie = ConsentProxy::cookieName(self::TOKEN);
        $this->transport = new FakeProxyTransport([
            'status'  => 200,
            'headers' => [
                ['Content-Type', 'application/json'],
                ['Set-Cookie', $this->cookie . '=eyJ2IjoxfQ; Domain=musteri.com; Path=/; Max-Age=31536000; Secure; SameSite=Lax'],
                ['X-Powered-By', 'PHP'],
                ['Set-Cookie', 'laravel_session=abc; Path=/; HttpOnly'],
                ['Access-Control-Allow-Origin', '*'],
            ],
            'body' => '{"status":true,"message":"Consent recorded"}',
        ]);
        $this->proxy = new ConsentProxy(self::TOKEN, self::KEY, 'https://live.veribenim.com', $this->transport);
    }

    private function consentRequest(array $headers = [], string $body = '{"action":"accept_all","session_id":"s-1"}', ?string $ip = '203.0.113.7'): ConsentProxyRequest
    {
        return new ConsentProxyRequest(
            method: 'POST',
            path: '/_vb/' . self::TOKEN . '/c',
            headers: array_merge([
                'Content-Type' => 'text/plain',
                'Origin'       => 'https://www.musteri.com',
            ], $headers),
            body: $body,
            clientIp: $ip,
        );
    }

    // ---- çerez adı -----------------------------------------------------------

    public function test_cookie_name_matches_api_formula(): void
    {
        $expected = 'vb_consent_' . substr(hash('sha256', 'vb-consent-cookie|' . self::TOKEN), 0, 8);
        $this->assertSame($expected, $this->cookie);
        $this->assertMatchesRegularExpression('/^vb_consent_[0-9a-f]{8}$/', $this->cookie);
    }

    // ---- yol izin listesi -----------------------------------------------------

    public function test_forwards_consent_post_to_vp_endpoint(): void
    {
        $response = $this->proxy->handle($this->consentRequest());

        $this->assertSame(200, $response->status);
        $sent = $this->transport->last();
        $this->assertSame('POST', $sent['method']);
        $this->assertSame('https://live.veribenim.com/api/vp/' . self::TOKEN . '/c', $sent['url']);
        $this->assertSame('{"action":"accept_all","session_id":"s-1"}', $sent['body']);
        $this->assertSame(5, $sent['timeout']);
    }

    public function test_ping_forwards_only_valid_nonce(): void
    {
        $nonce = str_repeat('aB3', 10) . 'xy';
        $this->proxy->handle(new ConsentProxyRequest('GET', '/_vb/' . self::TOKEN . '/ping', ['n' => $nonce, 'x' => 'y']));
        $this->assertSame('https://live.veribenim.com/api/vp/' . self::TOKEN . '/ping?n=' . $nonce, $this->transport->last()['url']);

        $this->proxy->handle(new ConsentProxyRequest('GET', '/_vb/' . self::TOKEN . '/ping', ['n' => '../../x&y=1']));
        $this->assertSame('https://live.veribenim.com/api/vp/' . self::TOKEN . '/ping', $this->transport->last()['url']);
    }

    public static function forbiddenPaths(): array
    {
        $t = self::TOKEN;
        return [
            'başka uç'              => ["/_vb/{$t}/e"],
            'impression'            => ["/_vb/{$t}/i"],
            'başka token'           => ['/_vb/ZZZZEFGH1234567890abcdEFGH123456/c'],
            'yol tırmanma'          => ["/_vb/{$t}/../../api/v1/me"],
            'kodlanmış tırmanma'    => ["/_vb/{$t}/%2e%2e/c"],
            'ek segment'            => ["/_vb/{$t}/c/extra"],
            'sondaki eğik çizgi'    => ["/_vb/{$t}/c/"],
            'önek dışı'             => ["/api/vp/{$t}/c"],
            'mutlak URL'            => ["/_vb/https://evil.example/{$t}/c"],
            'yalnız önek'           => ['/_vb'],
            'boş'                   => [''],
        ];
    }

    #[DataProvider('forbiddenPaths')]
    public function test_rejects_paths_outside_allowlist(string $path): void
    {
        $response = $this->proxy->handle(new ConsentProxyRequest('POST', $path, [], ['Content-Type' => 'text/plain'], '{}', '203.0.113.7'));

        $this->assertSame(404, $response->status);
        $this->assertSame([], $this->transport->sent, 'izinli olmayan yol upstream\'e gitmemeli');
    }

    public function test_wrong_method_is_405_and_not_forwarded(): void
    {
        $get = $this->proxy->handle(new ConsentProxyRequest('GET', '/_vb/' . self::TOKEN . '/c'));
        $post = $this->proxy->handle(new ConsentProxyRequest('POST', '/_vb/' . self::TOKEN . '/ping'));
        $del = $this->proxy->handle(new ConsentProxyRequest('DELETE', '/_vb/' . self::TOKEN . '/c'));

        $this->assertSame([405, 405, 405], [$get->status, $post->status, $del->status]);
        $this->assertSame(['POST'], $get->headerValues('Allow'));
        $this->assertSame([], $this->transport->sent);
    }

    public function test_oversized_body_is_rejected(): void
    {
        $response = $this->proxy->handle($this->consentRequest([], str_repeat('a', ConsentProxy::MAX_BODY_BYTES + 1)));

        $this->assertSame(413, $response->status);
        $this->assertSame([], $this->transport->sent);
    }

    // ---- istek başlıkları ------------------------------------------------------

    public function test_adds_proxy_key_and_client_ip_headers(): void
    {
        $this->proxy->handle($this->consentRequest());

        $headers = $this->transport->last()['headers'];
        $this->assertSame(self::KEY, $headers['X-VB-Proxy-Key']);
        $this->assertSame('203.0.113.7', $headers['X-VB-Client-IP']);
    }

    public function test_client_supplied_ip_and_key_headers_are_ignored(): void
    {
        $this->proxy->handle($this->consentRequest([
            'X-VB-Client-IP'  => '1.2.3.4',
            'X-VB-Proxy-Key'  => 'attacker',
            'X-Forwarded-For' => '9.9.9.9',
            'X-Real-IP'       => '8.8.8.8',
            'Authorization'   => 'Bearer secret',
            'Host'            => 'evil.example',
        ]));

        $headers = array_change_key_case($this->transport->last()['headers']);
        $this->assertSame('203.0.113.7', $headers['x-vb-client-ip']);
        $this->assertSame(self::KEY, $headers['x-vb-proxy-key']);
        $this->assertArrayNotHasKey('x-forwarded-for', $headers);
        $this->assertArrayNotHasKey('x-real-ip', $headers);
        $this->assertArrayNotHasKey('authorization', $headers);
        $this->assertArrayNotHasKey('host', $headers);
    }

    public function test_invalid_or_missing_client_ip_is_not_sent(): void
    {
        $this->proxy->handle($this->consentRequest([], '{}', 'not-an-ip'));
        $this->assertArrayNotHasKey('X-VB-Client-IP', $this->transport->last()['headers']);

        $this->proxy->handle($this->consentRequest([], '{}', null));
        $this->assertArrayNotHasKey('X-VB-Client-IP', $this->transport->last()['headers']);
    }

    public function test_cookie_header_is_reduced_to_site_consent_cookie(): void
    {
        $this->proxy->handle($this->consentRequest([
            'Cookie' => 'laravel_session=SESSION; ' . $this->cookie . '=eyJ2IjoxfQ; vb_consent_deadbeef=other; wordpress_logged_in_x=1',
        ]));

        $this->assertSame($this->cookie . '=eyJ2IjoxfQ', $this->transport->last()['headers']['cookie']);
    }

    public function test_cookie_header_is_dropped_without_consent_cookie(): void
    {
        $this->proxy->handle($this->consentRequest(['Cookie' => 'vb_probe_session=probe-1; sid=2']));

        $this->assertArrayNotHasKey('cookie', $this->transport->last()['headers']);
    }

    public function test_forwards_origin_and_content_type_for_origin_check(): void
    {
        $this->proxy->handle($this->consentRequest(['Referer' => 'https://www.musteri.com/sayfa', 'User-Agent' => 'UA/1']));

        $headers = $this->transport->last()['headers'];
        $this->assertSame('https://www.musteri.com', $headers['origin']);
        $this->assertSame('https://www.musteri.com/sayfa', $headers['referer']);
        $this->assertSame('text/plain', $headers['content-type']);
        $this->assertSame('UA/1', $headers['user-agent']);
    }

    // ---- yanıt -------------------------------------------------------------------

    public function test_set_cookie_for_site_consent_cookie_is_carried_verbatim(): void
    {
        $response = $this->proxy->handle($this->consentRequest());

        $this->assertSame(
            [$this->cookie . '=eyJ2IjoxfQ; Domain=musteri.com; Path=/; Max-Age=31536000; Secure; SameSite=Lax'],
            $response->headerValues('Set-Cookie')
        );
    }

    public function test_only_allowlisted_response_headers_are_returned(): void
    {
        $response = $this->proxy->handle($this->consentRequest());

        $names = array_map(fn ($h) => strtolower($h[0]), $response->headers);
        sort($names);
        $this->assertSame(['cache-control', 'content-type', 'set-cookie'], $names);
        $this->assertSame([], $response->headerValues('X-Powered-By'));
        $this->assertSame([], $response->headerValues('Access-Control-Allow-Origin'));
        $this->assertSame('{"status":true,"message":"Consent recorded"}', $response->body);
    }

    public function test_proxy_key_never_appears_in_response(): void
    {
        $this->transport->response = [
            'status'  => 403,
            'headers' => [['Content-Type', 'application/json'], ['X-Debug-Key', self::KEY], ['Cache-Control', 'x ' . self::KEY]],
            'body'    => '{"status":false,"code":"PROXY_KEY_INVALID","echo":"' . self::KEY . '"}',
        ];

        $response = $this->proxy->handle($this->consentRequest());

        $this->assertSame(403, $response->status);
        $this->assertStringNotContainsString(self::KEY, $response->body);
        foreach ($response->headers as [$name, $value]) {
            $this->assertStringNotContainsString(self::KEY, $name . $value);
        }
    }

    public function test_proxy_key_not_leaked_in_local_error_responses(): void
    {
        $responses = [
            $this->proxy->handle(new ConsentProxyRequest('POST', '/_vb/x/c')),
            $this->proxy->handle(new ConsentProxyRequest('GET', '/_vb/' . self::TOKEN . '/c')),
        ];
        $this->transport->response = new \RuntimeException('connect failed key=' . self::KEY);
        $responses[] = $this->proxy->handle($this->consentRequest());

        $this->assertSame(502, $responses[2]->status);
        foreach ($responses as $response) {
            $this->assertStringNotContainsString(self::KEY, $response->body);
            $this->assertStringNotContainsString(self::KEY, json_encode($response->headers));
        }
    }

    public function test_upstream_redirect_is_not_passed_through(): void
    {
        $this->transport->response = ['status' => 302, 'headers' => [['Location', 'https://evil.example/']], 'body' => ''];

        $response = $this->proxy->handle($this->consentRequest());

        $this->assertSame(502, $response->status);
        $this->assertSame([], $response->headerValues('Location'));
    }

    public function test_upstream_error_statuses_pass_through_for_bundle_fallback(): void
    {
        $this->transport->response = ['status' => 422, 'headers' => [['Content-Type', 'application/json']], 'body' => '{"message":"x"}'];

        $this->assertSame(422, $this->proxy->handle($this->consentRequest())->status);
    }

    // ---- yapılandırma -----------------------------------------------------------

    public function test_on_prem_api_url_is_used_for_upstream(): void
    {
        $config = new VeribenimConfig(token: self::TOKEN, apiUrl: 'https://veribenim.kurum.local/');
        $proxy = ConsentProxy::fromConfig($config, self::KEY, $this->transport);

        $proxy->handle($this->consentRequest());

        $this->assertSame('https://veribenim.kurum.local/api/vp/' . self::TOKEN . '/c', $this->transport->last()['url']);
    }

    public function test_constructor_rejects_bad_input(): void
    {
        foreach ([
            fn () => new ConsentProxy('bad/token', self::KEY),
            fn () => new ConsentProxy(self::TOKEN, '  '),
            fn () => new ConsentProxy(self::TOKEN, self::KEY, 'ftp://x'),
        ] as $factory) {
            try {
                $factory();
                $this->fail('InvalidArgumentException bekleniyordu');
            } catch (\InvalidArgumentException $e) {
                $this->assertStringNotContainsString(self::KEY, $e->getMessage());
            }
        }
    }

    public function test_handles_only_prefix(): void
    {
        $this->assertTrue($this->proxy->handles('/_vb/x/c'));
        $this->assertFalse($this->proxy->handles('/_vbx/c'));
        $this->assertFalse($this->proxy->handles('/iletisim'));
    }

    // ---- ClientIp ------------------------------------------------------------------

    public function test_client_ip_defaults_to_remote_addr_and_ignores_forwarded_header(): void
    {
        $server = ['REMOTE_ADDR' => '198.51.100.10', 'HTTP_X_FORWARDED_FOR' => '1.1.1.1'];

        $this->assertSame('198.51.100.10', ClientIp::resolve($server));
    }

    public function test_client_ip_reads_header_only_from_trusted_proxy(): void
    {
        $server = ['REMOTE_ADDR' => '10.0.0.5', 'HTTP_X_FORWARDED_FOR' => '6.6.6.6, 203.0.113.9, 10.0.0.2'];

        // Sağdan sola: 10.0.0.2 güvenilir → 203.0.113.9 ziyaretçi; soldaki uydurma değer alınmaz.
        $this->assertSame('203.0.113.9', ClientIp::resolve($server, ['10.0.0.0/8']));
        // TCP eşi güvenilir değilse başlık yok sayılır.
        $this->assertSame('10.0.0.5', ClientIp::resolve($server, ['192.168.0.0/16']));
    }

    public function test_client_ip_custom_header_and_ipv6(): void
    {
        $server = ['REMOTE_ADDR' => '2001:db8::1', 'HTTP_CF_CONNECTING_IP' => '2001:db8:ffff::7'];

        $this->assertSame('2001:db8:ffff::7', ClientIp::resolve($server, ['2001:db8::/112'], 'CF-Connecting-IP'));
        $this->assertNull(ClientIp::resolve(['REMOTE_ADDR' => 'garbage']));
        $this->assertSame('10.0.0.5', ClientIp::resolve(['REMOTE_ADDR' => '10.0.0.5', 'HTTP_X_FORWARDED_FOR' => 'evil'], ['10.0.0.5']));
    }
}
