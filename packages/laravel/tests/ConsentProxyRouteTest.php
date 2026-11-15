<?php

declare(strict_types=1);

namespace Veribenim\Laravel\Tests;

use Veribenim\Proxy\ConsentProxy;
use Veribenim\Proxy\ProxyTransport;
use Veribenim\Tests\Support\FakeProxyTransport;

class ConsentProxyRouteTest extends TestCase
{
    private const SITE_TOKEN = 'LaravelSiteToken1234567890abcdef';
    private const KEY = 'vbpk_laravelSECRETlaravelSECRETlaravel1234';

    private FakeProxyTransport $transport;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('veribenim.token', self::SITE_TOKEN);
        $app['config']->set('veribenim.consent_proxy.enabled', true);
        $app['config']->set('veribenim.consent_proxy.proxy_key', self::KEY);

        $this->transport = new FakeProxyTransport([
            'status'  => 200,
            'headers' => [
                ['Content-Type', 'application/json'],
                ['Set-Cookie', ConsentProxy::cookieName(self::SITE_TOKEN) . '=eyJ2IjoxfQ; Domain=example.com; Path=/; Max-Age=15552000; Secure; SameSite=Lax'],
                ['Set-Cookie', 'other=1; Path=/'],
            ],
            'body' => '{"status":true}',
        ]);
        $app->instance(ProxyTransport::class, $this->transport);
    }

    public function test_consent_post_is_forwarded_with_key_and_client_ip(): void
    {
        $response = $this->call(
            'POST',
            '/_vb/' . self::SITE_TOKEN . '/c',
            [],
            [],
            [],
            ['HTTP_COOKIE' => 'laravel_session=secret-session; ' . ConsentProxy::cookieName(self::SITE_TOKEN) . '=eyJ2IjoxfQ', 'CONTENT_TYPE' => 'text/plain', 'HTTP_ORIGIN' => 'https://www.example.com', 'REMOTE_ADDR' => '203.0.113.20', 'HTTP_X_FORWARDED_FOR' => '6.6.6.6'],
            '{"action":"accept_all","session_id":"s-1"}'
        );

        $response->assertStatus(200);
        $sent = $this->transport->last();
        $this->assertSame('https://live.veribenim.com/api/vp/' . self::SITE_TOKEN . '/c', $sent['url']);
        $this->assertSame(self::KEY, $sent['headers']['X-VB-Proxy-Key']);
        // TrustProxies yapılandırılmadığı için X-Forwarded-For yok sayılır.
        $this->assertSame('203.0.113.20', $sent['headers']['X-VB-Client-IP']);
        $this->assertSame(ConsentProxy::cookieName(self::SITE_TOKEN) . '=eyJ2IjoxfQ', $sent['headers']['cookie']);
        $this->assertSame('{"action":"accept_all","session_id":"s-1"}', $sent['body']);
    }

    public function test_set_cookie_is_carried_back_and_key_not_exposed(): void
    {
        $response = $this->call('POST', '/_vb/' . self::SITE_TOKEN . '/c', [], [], [], ['CONTENT_TYPE' => 'text/plain'], '{}');

        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $cookie = $cookies[0];
        $this->assertSame(ConsentProxy::cookieName(self::SITE_TOKEN), $cookie->getName());
        $this->assertSame('eyJ2IjoxfQ', $cookie->getValue());
        $this->assertSame('example.com', $cookie->getDomain());
        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $cookie->getSameSite()));
        $this->assertSame(15552000, $cookie->getMaxAge());

        $this->assertStringNotContainsString(self::KEY, (string) $response->getContent());
        $this->assertStringNotContainsString(self::KEY, (string) $response->headers);
    }

    public function test_other_paths_and_tokens_are_not_proxied(): void
    {
        $this->call('POST', '/_vb/' . self::SITE_TOKEN . '/e', [], [], [], [], '{}')->assertStatus(404);
        $this->call('POST', '/_vb/OtherSiteToken1234567890abcdefgh/c', [], [], [], [], '{}')->assertStatus(404);
        $this->call('GET', '/_vb/' . self::SITE_TOKEN . '/c')->assertStatus(405);

        $this->assertSame([], $this->transport->sent);
    }

    public function test_proxy_routes_have_no_session_or_csrf_middleware(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('veribenim.consent-proxy.consent');

        $this->assertNotNull($route);
        $this->assertNotContains('web', $route->gatherMiddleware());
    }

    public function test_ping_is_forwarded(): void
    {
        $nonce = str_repeat('Ab1', 8);
        $this->get('/_vb/' . self::SITE_TOKEN . '/ping?n=' . $nonce)->assertStatus(200);

        $this->assertSame(
            'https://live.veribenim.com/api/vp/' . self::SITE_TOKEN . '/ping?n=' . $nonce,
            $this->transport->last()['url']
        );
    }
}
