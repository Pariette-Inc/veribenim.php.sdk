<?php

declare(strict_types=1);

namespace Veribenim\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Veribenim\VeribenimClient;
use Veribenim\VeribenimConfig;

/**
 * Gerçek HTTP katmanı (curl yolu) entegrasyon testleri.
 * PHP built-in server ile 2xx/4xx/5xx/bozuk JSON/timeout senaryoları doğrulanır.
 */
#[Group('http')]
class VeribenimClientHttpTest extends TestCase
{
    private static string $baseUrl;

    /** @var resource|null */
    private static $serverProcess = null;

    public static function setUpBeforeClass(): void
    {
        $port = self::findFreePort();
        self::$baseUrl = "http://127.0.0.1:{$port}";

        $router = __DIR__ . '/Support/test-server-router.php';
        self::$serverProcess = proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:{$port}", $router],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes
        );

        if (!is_resource(self::$serverProcess)) {
            self::markTestSkipped('PHP built-in server başlatılamadı');
        }

        // Sunucunun ayağa kalkmasını bekle (en fazla ~2 sn)
        $deadline = microtime(true) + 2.0;
        while (microtime(true) < $deadline) {
            $conn = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($conn !== false) {
                fclose($conn);
                return;
            }
            usleep(50_000);
        }

        self::markTestSkipped('PHP built-in server yanıt vermiyor');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$serverProcess)) {
            proc_terminate(self::$serverProcess);
            proc_close(self::$serverProcess);
        }
    }

    private static function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($socket === false) {
            self::markTestSkipped('Serbest port bulunamadı: ' . $errstr);
        }
        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($name, strrpos($name, ':') + 1);
    }

    private function client(string $token, int $timeout = 5): VeribenimClient
    {
        return new VeribenimClient(new VeribenimConfig(
            token: $token,
            apiUrl: self::$baseUrl,
            timeout: $timeout,
        ));
    }

    public function test_successful_get_returns_decoded_json(): void
    {
        $result = $this->client('oktoken1234567890')->getPreferences('sess-1');

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertSame('GET', $result['method']);
        $this->assertSame('/api/preferences/oktoken1234567890?session_id=sess-1', $result['uri']);
    }

    public function test_successful_post_sends_json_body(): void
    {
        $result = $this->client('oktoken1234567890')->savePreferences(['analytics' => true], 'sess-2');

        $this->assertIsArray($result);
        $this->assertSame('POST', $result['method']);
        $this->assertSame(['consents' => ['analytics' => true], 'session_id' => 'sess-2'], $result['body']);
    }

    public function test_404_response_returns_null(): void
    {
        $this->assertNull($this->client('err404token1234567')->getPreferences());
    }

    public function test_500_response_returns_null(): void
    {
        $this->assertNull($this->client('err500token1234567')->getPreferences());
    }

    public function test_invalid_json_body_returns_null(): void
    {
        $this->assertNull($this->client('badjsontoken123456')->getPreferences());
    }

    public function test_unreachable_host_returns_null_and_false(): void
    {
        // Kimsenin dinlemediği bir porta bağlan → bağlantı hatası
        $deadPort = self::findFreePort();
        $client = new VeribenimClient(new VeribenimConfig(
            token: 'deadtoken123456789',
            apiUrl: "http://127.0.0.1:{$deadPort}",
            timeout: 1,
        ));

        $this->assertNull($client->getPreferences());
        $this->assertFalse($client->logConsent('accept_all'));
        $this->assertFalse($client->collectAnalytics(['sid' => 's', 'url' => '/x']));
    }

    public function test_timeout_returns_null(): void
    {
        // Sunucu 3 sn bekler, timeout 1 sn → null dönmeli
        $this->assertNull($this->client('slowtok1234567890', timeout: 1)->getPreferences());
    }

    public function test_beacon_endpoint_204_returns_true(): void
    {
        $ok = $this->client('oktoken1234567890')->collectAnalytics([
            'sid' => 'uuid-session-1',
            'url' => 'https://claude.com/',
        ]);

        $this->assertTrue($ok);
    }

    // -------------------------------------------------------------------------
    // CurlProxyTransport — gerçek HTTP ile rıza proxy'si
    // -------------------------------------------------------------------------

    public function test_consent_proxy_over_real_http_carries_cookie_and_hides_key(): void
    {
        $token = 'ProxyHttpToken1234567890abcdefgh';
        $key = 'vbpk_httpSECRETkey1234567890abcdefgh';
        $proxy = new \Veribenim\Proxy\ConsentProxy($token, $key, self::$baseUrl);
        $cookie = \Veribenim\Proxy\ConsentProxy::cookieName($token);

        $response = $proxy->handle(new \Veribenim\Proxy\ConsentProxyRequest(
            method: 'POST',
            path: '/_vb/' . $token . '/c',
            headers: [
                'Content-Type'    => 'text/plain',
                'Cookie'          => 'PHPSESSID=s3cr3t; ' . $cookie . '=old',
                'X-Forwarded-For' => '6.6.6.6',
            ],
            body: '{"action":"accept_all"}',
            clientIp: '203.0.113.77',
        ));

        $this->assertSame(200, $response->status);
        $this->assertSame([$cookie . '=eyJ2IjoxfQ; Path=/; Max-Age=600; SameSite=Lax'], $response->headerValues('Set-Cookie'));
        $this->assertSame([], $response->headerValues('X-Upstream-Secret'));

        $echo = json_decode($response->body, true);
        // Upstream anahtarı aldı, ama yansıttığı değer istemciye maskeli döner.
        $this->assertSame('[redacted]', $echo['key']);
        $this->assertStringNotContainsString($key, $response->body);
        $this->assertSame('203.0.113.77', $echo['client_ip']);
        $this->assertSame($cookie . '=old', $echo['cookie']);
        $this->assertNull($echo['xff']);
        $this->assertSame('{"action":"accept_all"}', $echo['body']);
    }

    public function test_consent_proxy_does_not_follow_upstream_redirect(): void
    {
        $token = 'ProxyRedirToken1234567890abcdefg';
        $proxy = new \Veribenim\Proxy\ConsentProxy($token, 'vbpk_key1234567890abcdef', self::$baseUrl);

        $response = $proxy->handle(new \Veribenim\Proxy\ConsentProxyRequest('POST', '/_vb/' . $token . '/c', [], [], '{}', '1.2.3.4'));

        $this->assertSame(502, $response->status);
        $this->assertSame([], $response->headerValues('Location'));
    }
}
