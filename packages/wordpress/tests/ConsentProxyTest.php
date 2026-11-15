<?php

declare(strict_types=1);

namespace Veribenim\WordPress\Tests;

use PHPUnit\Framework\TestCase;
use Veribenim_Consent_Proxy;
use VeribenimWpTestState;
use WP_Error;

class ConsentProxyTest extends TestCase
{
    private const TOKEN = 'WpSiteToken1234567890abcdefABCDE';
    private const KEY = 'vbpk_wordpressSECRETwordpressSECRET123456';

    private Veribenim_Consent_Proxy $proxy;
    private string $cookie;
    /** @var array<int, array> */
    private array $emitted = [];

    protected function setUp(): void
    {
        VeribenimWpTestState::reset();
        VeribenimWpTestState::$options['veribenim_token'] = self::TOKEN;
        VeribenimWpTestState::$options['veribenim_proxy_key'] = self::KEY;
        VeribenimWpTestState::$options['veribenim_proxy_enabled'] = true;
        $this->cookie = Veribenim_Consent_Proxy::cookie_name(self::TOKEN);

        VeribenimWpTestState::$remoteRequestResponse = [
            'response' => ['code' => 200],
            'headers'  => [
                'content-type' => 'application/json',
                // WordPress aynı addaki başlıkları dizi olarak verir.
                'set-cookie'   => [
                    $this->cookie . '=eyJ2IjoxfQ; Domain=claude.com; Path=/; Max-Age=31536000; Secure; SameSite=Lax',
                    'wp_other=1; Path=/',
                ],
                'x-powered-by' => 'PHP',
            ],
            'body' => '{"status":true}',
        ];

        $this->emitted = [];
        $this->proxy = new Veribenim_Consent_Proxy(function (array $r): void {
            $this->emitted[] = $r;
        });
    }

    private function post(array $headers = [], string $ip = '203.0.113.50'): array
    {
        return $this->proxy->handle(
            'POST',
            '/_vb/' . self::TOKEN . '/c',
            array_merge(['content-type' => 'text/plain', 'origin' => 'https://claude.com'], $headers),
            '{"action":"accept_all","session_id":"s"}',
            $ip
        );
    }

    private function lastRequest(): array
    {
        return end(VeribenimWpTestState::$remoteRequests) ?: [];
    }

    public function test_cookie_name_matches_core_and_api(): void
    {
        $this->assertSame(\Veribenim\Proxy\ConsentProxy::cookieName(self::TOKEN), $this->cookie);
    }

    public function test_forwards_consent_with_key_ip_and_filtered_cookie(): void
    {
        $result = $this->post([
            'cookie'          => 'wordpress_logged_in_abc=admin; ' . $this->cookie . '=eyJ2IjoxfQ; vb_probe_session=x',
            'x-forwarded-for' => '6.6.6.6',
            'authorization'   => 'Basic xyz',
        ]);

        $this->assertSame(200, $result['status']);
        $req = $this->lastRequest();
        $this->assertSame('https://live.veribenim.com/api/vp/' . self::TOKEN . '/c', $req['url']);
        $this->assertSame('POST', $req['args']['method']);
        $this->assertSame(0, $req['args']['redirection']);
        $this->assertTrue($req['args']['sslverify']);
        $h = $req['args']['headers'];
        $this->assertSame(self::KEY, $h['X-VB-Proxy-Key']);
        $this->assertSame('203.0.113.50', $h['X-VB-Client-IP']);
        $this->assertSame($this->cookie . '=eyJ2IjoxfQ', $h['cookie']);
        $this->assertSame('https://claude.com', $h['origin']);
        $this->assertArrayNotHasKey('x-forwarded-for', $h);
        $this->assertArrayNotHasKey('authorization', $h);
    }

    public function test_set_cookie_carried_verbatim_and_other_headers_dropped(): void
    {
        $result = $this->post();

        $setCookies = array_values(array_filter($result['headers'], fn ($h) => $h[0] === 'Set-Cookie'));
        $this->assertSame(
            [['Set-Cookie', $this->cookie . '=eyJ2IjoxfQ; Domain=claude.com; Path=/; Max-Age=31536000; Secure; SameSite=Lax']],
            $setCookies
        );
        $names = array_map(fn ($h) => strtolower($h[0]), $result['headers']);
        $this->assertNotContains('x-powered-by', $names);
    }

    public function test_disallowed_paths_tokens_and_methods_are_rejected(): void
    {
        $this->assertSame(404, $this->proxy->handle('POST', '/_vb/' . self::TOKEN . '/e', [], '{}', '1.2.3.4')['status']);
        $this->assertSame(404, $this->proxy->handle('POST', '/_vb/OtherToken1234567890abcdefABCDE/c', [], '{}', '1.2.3.4')['status']);
        $this->assertSame(404, $this->proxy->handle('POST', '/_vb/' . self::TOKEN . '/../../wp-admin/c', [], '{}', '1.2.3.4')['status']);
        $this->assertSame(405, $this->proxy->handle('GET', '/_vb/' . self::TOKEN . '/c', [], '', '1.2.3.4')['status']);
        $this->assertSame(413, $this->proxy->handle('POST', '/_vb/' . self::TOKEN . '/c', [], str_repeat('x', 65537), '1.2.3.4')['status']);

        $this->assertSame([], VeribenimWpTestState::$remoteRequests);
    }

    public function test_invalid_client_ip_not_forwarded(): void
    {
        $this->post([], 'bilinmiyor');

        $this->assertArrayNotHasKey('X-VB-Client-IP', $this->lastRequest()['args']['headers']);
    }

    public function test_key_never_in_response(): void
    {
        VeribenimWpTestState::$remoteRequestResponse = [
            'response' => ['code' => 403],
            'headers'  => ['content-type' => 'application/json', 'x-echo' => self::KEY],
            'body'     => '{"code":"PROXY_KEY_INVALID","k":"' . self::KEY . '"}',
        ];
        $responses = [$this->post()];

        VeribenimWpTestState::$remoteRequestResponse = new WP_Error('http_request_failed', 'cURL error key=' . self::KEY);
        $responses[] = $this->post();

        $this->assertSame([403, 502], [$responses[0]['status'], $responses[1]['status']]);
        foreach ($responses as $r) {
            $this->assertStringNotContainsString(self::KEY, $r['body']);
            $this->assertStringNotContainsString(self::KEY, json_encode($r['headers']));
        }
    }

    public function test_redirect_is_not_followed_or_passed(): void
    {
        VeribenimWpTestState::$remoteRequestResponse = [
            'response' => ['code' => 301],
            'headers'  => ['location' => 'https://evil.example/'],
            'body'     => '',
        ];

        $this->assertSame(502, $this->post()['status']);
    }

    public function test_ping_forwards_valid_nonce_only(): void
    {
        $nonce = str_repeat('Zz9', 8);
        $this->proxy->handle('GET', '/_vb/' . self::TOKEN . '/ping', [], '', '1.2.3.4', $nonce);
        $this->assertSame('https://live.veribenim.com/api/vp/' . self::TOKEN . '/ping?n=' . $nonce, $this->lastRequest()['url']);

        $this->proxy->handle('GET', '/_vb/' . self::TOKEN . '/ping', [], '', '1.2.3.4', 'x&y=1');
        $this->assertSame('https://live.veribenim.com/api/vp/' . self::TOKEN . '/ping', $this->lastRequest()['url']);
    }

    public function test_api_url_filter_changes_upstream_for_on_prem(): void
    {
        VeribenimWpTestState::$filters['veribenim_api_url'] = fn () => 'https://api.kurum.local/';

        $this->post();

        $this->assertSame('https://api.kurum.local/api/vp/' . self::TOKEN . '/c', $this->lastRequest()['url']);
    }

    public function test_maybe_handle_ignores_other_paths_and_disabled_state(): void
    {
        $_SERVER['REQUEST_URI'] = '/iletisim/';
        $this->proxy->maybe_handle();
        $this->assertSame([], $this->emitted);

        VeribenimWpTestState::$options['veribenim_proxy_enabled'] = false;
        $_SERVER['REQUEST_URI'] = '/_vb/' . self::TOKEN . '/c';
        $this->proxy->maybe_handle();
        $this->assertSame([], $this->emitted);
        unset($_SERVER['REQUEST_URI']);
    }

    public function test_maybe_handle_uses_remote_addr_and_client_ip_filter(): void
    {
        $_SERVER['REQUEST_URI'] = '/_vb/' . self::TOKEN . '/c?x=1';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '198.51.100.4';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '6.6.6.6';

        $this->proxy->maybe_handle();
        $this->assertCount(1, $this->emitted);
        $this->assertSame('198.51.100.4', $this->lastRequest()['args']['headers']['X-VB-Client-IP']);

        VeribenimWpTestState::$filters['veribenim_proxy_client_ip'] = fn ($ip) => '203.0.113.99';
        $this->proxy->maybe_handle();
        $this->assertSame('203.0.113.99', $this->lastRequest()['args']['headers']['X-VB-Client-IP']);

        unset($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    public function test_disabled_without_key(): void
    {
        VeribenimWpTestState::$options['veribenim_proxy_key'] = '';

        $this->assertFalse(Veribenim_Consent_Proxy::is_enabled());
    }
}
