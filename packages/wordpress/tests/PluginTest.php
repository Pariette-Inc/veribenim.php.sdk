<?php

declare(strict_types=1);

namespace Veribenim\WordPress\Tests;

use PHPUnit\Framework\TestCase;
use Veribenim_Plugin;
use VeribenimWpTestState;

class PluginTest extends TestCase
{
    private const TOKEN = 'wp-testtoken-1234567890abcdef12';

    private Veribenim_Plugin $plugin;

    protected function setUp(): void
    {
        VeribenimWpTestState::reset();
        $this->plugin = new Veribenim_Plugin();
    }

    private function withToken(): void
    {
        VeribenimWpTestState::$options['veribenim_token'] = self::TOKEN;
    }

    private function withSchema(array $schema): void
    {
        VeribenimWpTestState::$remoteResponse = [
            'response' => ['code' => 200],
            'body'     => json_encode($schema),
        ];
    }

    // -------------------------------------------------------------------------
    // run() — hook kaydı
    // -------------------------------------------------------------------------

    public function test_run_registers_frontend_hook_and_shortcode(): void
    {
        $this->plugin->run();

        $this->assertArrayHasKey('wp_head', VeribenimWpTestState::$actions);
        $this->assertSame(1, VeribenimWpTestState::$actions['wp_head'][0]['priority']);
        $this->assertArrayHasKey('veribenim_form', VeribenimWpTestState::$shortcodes);
        // is_admin=false → admin hook'ları kaydedilmez
        $this->assertArrayNotHasKey('admin_menu', VeribenimWpTestState::$actions);
    }

    public function test_run_registers_admin_hooks_in_admin(): void
    {
        VeribenimWpTestState::$isAdmin = true;

        $this->plugin->run();

        $this->assertArrayHasKey('admin_menu', VeribenimWpTestState::$actions);
        $this->assertArrayHasKey('admin_init', VeribenimWpTestState::$actions);
        $this->assertArrayHasKey('wp_ajax_veribenim_test_connection', VeribenimWpTestState::$actions);
    }

    // -------------------------------------------------------------------------
    // [veribenim_form] shortcode — guard'lar
    // -------------------------------------------------------------------------

    public function test_shortcode_requires_slug(): void
    {
        $this->withToken();

        $output = $this->plugin->render_form_shortcode([]);

        $this->assertStringContainsString('requires slug attribute', $output);
        $this->assertSame([], VeribenimWpTestState::$remoteRequests);
    }

    public function test_shortcode_requires_token(): void
    {
        $output = $this->plugin->render_form_shortcode(['slug' => 'iletisim']);

        $this->assertStringContainsString('token not configured', $output);
    }

    // -------------------------------------------------------------------------
    // JS modu
    // -------------------------------------------------------------------------

    public function test_js_mode_renders_container_and_loader_script(): void
    {
        $this->withToken();

        $output = $this->plugin->render_form_shortcode(['slug' => 'iletisim', 'mode' => 'js']);

        $this->assertStringContainsString('<div id="vb-form-iletisim"', $output);
        $this->assertStringContainsString('data-vb-form="iletisim"', $output);
        $this->assertStringContainsString('data-vb-token="' . self::TOKEN . '"', $output);
        $this->assertStringContainsString('veribenim.renderForm("iletisim","#vb-form-iletisim")', $output);
        // JS modunda API'ye gidilmez
        $this->assertSame([], VeribenimWpTestState::$remoteRequests);
    }

    public function test_js_mode_honors_custom_id_and_lang(): void
    {
        $this->withToken();

        $output = $this->plugin->render_form_shortcode([
            'slug' => 'iletisim',
            'mode' => 'js',
            'id'   => 'ozel-id',
            'lang' => 'de',
        ]);

        $this->assertStringContainsString('<div id="ozel-id"', $output);
        $this->assertStringContainsString('renderForm("iletisim","#ozel-id",{lang:"de"})', $output);
    }

    // -------------------------------------------------------------------------
    // HTML modu
    // -------------------------------------------------------------------------

    public function test_html_mode_fetches_schema_and_renders_form(): void
    {
        $this->withToken();
        $this->withSchema([
            'slug'   => 'iletisim',
            'type'   => 'single_step',
            'fields' => [
                ['uuid' => 'u-msg', 'type' => 'textarea', 'label' => 'Mesajınız', 'order' => 2],
                ['uuid' => 'u-name', 'type' => 'input', 'label' => 'Adınız', 'order' => 1, 'required' => true],
            ],
            'settings' => ['submit_button_text' => 'Gönder'],
        ]);

        $output = $this->plugin->render_form_shortcode(['slug' => 'iletisim']);

        // Schema doğru URL'den çekilmiş mi?
        $this->assertCount(1, VeribenimWpTestState::$remoteRequests);
        $this->assertSame(
            'https://live.veribenim.com/api/public/forms/' . rawurlencode(self::TOKEN) . '/iletisim',
            VeribenimWpTestState::$remoteRequests[0]['url']
        );

        $this->assertStringContainsString('<form id="vb-form-iletisim"', $output);
        $this->assertStringContainsString(
            'data-vb-action="https://live.veribenim.com/api/public/forms/' . rawurlencode(self::TOKEN) . '/iletisim"',
            $output
        );
        // order'a göre sıralama
        $this->assertLessThan(strpos($output, 'u-msg'), strpos($output, 'u-name'));
        $this->assertStringContainsString('<textarea id="vb-u-msg"', $output);
        $this->assertStringContainsString('required aria-required="true"', $output);
        $this->assertStringContainsString('>Gönder</button>', $output);
    }

    public function test_html_mode_appends_lang_to_schema_url(): void
    {
        $this->withToken();
        $this->withSchema(['slug' => 'iletisim', 'fields' => []]);

        $this->plugin->render_form_shortcode(['slug' => 'iletisim', 'lang' => 'en']);

        $this->assertStringEndsWith('?lang=en', VeribenimWpTestState::$remoteRequests[0]['url']);
    }

    public function test_html_mode_uses_custom_api_url_option(): void
    {
        $this->withToken();
        VeribenimWpTestState::$options['veribenim_api_url'] = 'https://staging.veribenim.com/';
        $this->withSchema(['slug' => 'iletisim', 'fields' => []]);

        $this->plugin->render_form_shortcode(['slug' => 'iletisim']);

        $this->assertStringStartsWith(
            'https://staging.veribenim.com/api/public/forms/',
            VeribenimWpTestState::$remoteRequests[0]['url']
        );
    }

    public function test_html_mode_error_shows_message_only_to_admins(): void
    {
        $this->withToken();
        VeribenimWpTestState::$remoteResponse = ['response' => ['code' => 500], 'body' => ''];

        VeribenimWpTestState::$currentUserCan = true;
        $adminOutput = $this->plugin->render_form_shortcode(['slug' => 'iletisim']);
        $this->assertStringContainsString('formu yüklenemedi', $adminOutput);

        VeribenimWpTestState::$currentUserCan = false;
        $visitorOutput = $this->plugin->render_form_shortcode(['slug' => 'iletisim']);
        $this->assertSame('', $visitorOutput);
    }

    public function test_html_mode_wp_error_returns_empty_for_visitors(): void
    {
        $this->withToken();
        VeribenimWpTestState::$remoteResponse = new \WP_Error('http_request_failed', 'bağlantı hatası');
        VeribenimWpTestState::$currentUserCan = false;

        $this->assertSame('', $this->plugin->render_form_shortcode(['slug' => 'iletisim']));
    }

    public function test_html_mode_escapes_field_labels(): void
    {
        $this->withToken();
        $this->withSchema([
            'slug'   => 'xss',
            'fields' => [
                ['uuid' => 'u-1', 'type' => 'input', 'label' => '<script>alert(1)</script>'],
            ],
        ]);

        $output = $this->plugin->render_form_shortcode(['slug' => 'xss']);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $output);
    }

    // -------------------------------------------------------------------------
    // Inline submit script — redirect URL güvenliği
    // -------------------------------------------------------------------------

    public function test_inline_script_includes_valid_https_redirect(): void
    {
        $this->withToken();
        $this->withSchema([
            'slug'     => 'iletisim',
            'fields'   => [],
            'settings' => ['redirect_url' => 'https://claude.com/tesekkurler'],
        ]);

        $output = $this->plugin->render_form_shortcode(['slug' => 'iletisim']);

        $this->assertStringContainsString("window.location.href='https://claude.com/tesekkurler'", $output);
    }

    public function test_inline_script_blocks_javascript_uri_redirect(): void
    {
        $this->withToken();
        $this->withSchema([
            'slug'     => 'iletisim',
            'fields'   => [],
            'settings' => ['redirect_url' => 'javascript:alert(document.cookie)'],
        ]);

        $output = $this->plugin->render_form_shortcode(['slug' => 'iletisim']);

        $this->assertStringNotContainsString('javascript:alert', $output);
        $this->assertStringNotContainsString('window.location.href', $output);
    }
}
