<?php

declare(strict_types=1);

namespace Veribenim\Laravel\Tests;

use Illuminate\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use Veribenim\Laravel\ConsentHelper;
use Veribenim\VeribenimClient;

class ConsentHelperTest extends TestCase
{
    private VeribenimClient&MockObject $client;

    private ConsentHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(VeribenimClient::class);
        $this->helper = new ConsentHelper($this->client);
    }

    private function setRequest(array $cookies = [], array $headers = []): void
    {
        $request = Request::create('/', 'GET', [], $cookies);
        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }

        $this->app->instance('request', $request);
    }

    public function test_preferences_returns_null_without_session(): void
    {
        $this->setRequest();
        $this->client->expects($this->never())->method('getPreferences');

        $this->assertNull($this->helper->preferences());
        $this->assertFalse($this->helper->isConsented('analytics'));
    }

    public function test_preferences_reads_session_from_cookie(): void
    {
        $this->setRequest(cookies: ['veribenim_session' => 'sess-cookie']);
        $this->client->expects($this->once())
            ->method('getPreferences')
            ->with('sess-cookie')
            ->willReturn(['current_consents' => ['analytics' => true]]);

        $this->assertSame(['analytics' => true], $this->helper->preferences());
    }

    public function test_preferences_falls_back_to_header(): void
    {
        $this->setRequest(headers: ['X-Veribenim-Session' => 'sess-header']);
        $this->client->expects($this->once())
            ->method('getPreferences')
            ->with('sess-header')
            ->willReturn(['current_consents' => ['marketing' => false]]);

        $this->assertSame(['marketing' => false], $this->helper->preferences());
    }

    public function test_preferences_are_cached_per_session(): void
    {
        $this->setRequest(cookies: ['veribenim_session' => 'sess-1']);
        $this->client->expects($this->once())
            ->method('getPreferences')
            ->willReturn(['current_consents' => ['analytics' => true]]);

        $this->helper->preferences();
        $this->helper->preferences();
        $this->helper->isConsented('analytics');
    }

    public function test_null_api_response_is_cached_and_returns_null(): void
    {
        $this->setRequest(cookies: ['veribenim_session' => 'sess-1']);
        $this->client->expects($this->once())
            ->method('getPreferences')
            ->willReturn(null);

        $this->assertNull($this->helper->preferences());
        $this->assertNull($this->helper->preferences());
    }

    public function test_is_consented_checks_all_given_categories(): void
    {
        $this->setRequest(cookies: ['veribenim_session' => 'sess-1']);
        $this->client->method('getPreferences')->willReturn([
            'current_consents' => [
                'strictly_necessary' => true,
                'analytics'          => true,
                'marketing'          => false,
            ],
        ]);

        $this->assertTrue($this->helper->isConsented('analytics'));
        $this->assertFalse($this->helper->isConsented('marketing'));
        $this->assertFalse($this->helper->isConsented('analytics', 'marketing'));
        $this->assertTrue($this->helper->isConsented('strictly_necessary', 'analytics'));
        // Bilinmeyen kategori → rıza yok sayılır
        $this->assertFalse($this->helper->isConsented('bilinmeyen'));
    }

    public function test_is_consented_uses_configured_default_categories(): void
    {
        config(['veribenim.middleware.required_categories' => ['functional']]);

        $this->setRequest(cookies: ['veribenim_session' => 'sess-1']);
        $this->client->method('getPreferences')->willReturn([
            'current_consents' => ['functional' => true],
        ]);

        $this->assertTrue($this->helper->isConsented());
    }
}
