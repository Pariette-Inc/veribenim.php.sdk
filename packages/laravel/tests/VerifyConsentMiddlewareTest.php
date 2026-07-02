<?php

declare(strict_types=1);

namespace Veribenim\Laravel\Tests;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\MockObject\MockObject;
use Veribenim\VeribenimClient;

class VerifyConsentMiddlewareTest extends TestCase
{
    private VeribenimClient&MockObject $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(VeribenimClient::class);
        $this->app->instance(VeribenimClient::class, $this->client);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/korumali', fn () => 'ok')->middleware('veribenim.consent:analytics');
        $router->get('/varsayilan', fn () => 'ok')->middleware('veribenim.consent');
    }

    public function test_redirects_when_session_missing(): void
    {
        $this->get('/korumali')->assertRedirect('/');
    }

    public function test_returns_403_json_when_session_missing_and_expects_json(): void
    {
        $this->getJson('/korumali')
            ->assertStatus(403)
            ->assertJson(['error' => 'Çerez rızası gereklidir']);
    }

    public function test_redirects_when_preferences_lookup_fails(): void
    {
        $this->client->method('getPreferences')->with('sess-1')->willReturn(null);

        $this->withHeader('X-Veribenim-Session', 'sess-1')
            ->get('/korumali')
            ->assertRedirect('/');
    }

    public function test_redirects_to_configured_url(): void
    {
        config(['veribenim.middleware.redirect_to' => '/cerez-politikasi']);

        $this->get('/korumali')->assertRedirect('/cerez-politikasi');
    }

    public function test_allows_request_when_category_consented(): void
    {
        $this->client->method('getPreferences')->with('sess-1')->willReturn([
            'current_consents' => ['strictly_necessary' => true, 'analytics' => true],
        ]);

        $this->withHeader('X-Veribenim-Session', 'sess-1')
            ->get('/korumali')
            ->assertOk()
            ->assertSee('ok');
    }

    public function test_blocks_request_when_category_rejected(): void
    {
        $this->client->method('getPreferences')->willReturn([
            'current_consents' => ['strictly_necessary' => true, 'analytics' => false],
        ]);

        $this->withHeader('X-Veribenim-Session', 'sess-1')
            ->getJson('/korumali')
            ->assertStatus(403)
            ->assertJson(['error' => "'analytics' kategorisi için rıza gereklidir"]);
    }

    public function test_session_can_come_from_unencrypted_cookie(): void
    {
        $this->client->method('getPreferences')->with('sess-cookie')->willReturn([
            'current_consents' => ['analytics' => true],
        ]);

        $this->withUnencryptedCookie('veribenim_session', 'sess-cookie')
            ->get('/korumali')
            ->assertOk();
    }

    public function test_default_categories_from_config_are_enforced(): void
    {
        $this->client->method('getPreferences')->willReturn([
            'current_consents' => ['strictly_necessary' => false],
        ]);

        $this->withHeader('X-Veribenim-Session', 'sess-1')
            ->getJson('/varsayilan')
            ->assertStatus(403)
            ->assertJson(['error' => "'strictly_necessary' kategorisi için rıza gereklidir"]);
    }
}
