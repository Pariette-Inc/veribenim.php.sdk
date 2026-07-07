<?php

declare(strict_types=1);

namespace Veribenim\WordPress\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Veribenim_Frontend;
use VeribenimWpTestState;

class FrontendTest extends TestCase
{
    protected function setUp(): void
    {
        VeribenimWpTestState::reset();
    }

    /**
     * Core VeribenimConfig::cleanDomainForFilename() ile birebir aynı
     * davranış beklenir (backend bundle isimlendirmesiyle uyum).
     */
    #[DataProvider('domainProvider')]
    public function test_clean_domain_for_filename(string $input, string $expected): void
    {
        $this->assertSame($expected, Veribenim_Frontend::clean_domain_for_filename($input));
    }

    public static function domainProvider(): array
    {
        return [
            'düz domain'          => ['claude.com', 'claudecom'],
            'https protokol'      => ['https://claude.com', 'claudecom'],
            'http + www'          => ['http://www.claude.com', 'claudecom'],
            'path ve query'       => ['https://www.example.com/path/page?x=1', 'examplecompathpagex1'],
            'port'                => ['example.com:8080', 'examplecom8080'],
            'boş string fallback' => ['', 'bundle'],
        ];
    }

    public function test_matches_core_sdk_domain_cleaning(): void
    {
        foreach (['claude.com', 'https://www.example.com/x', 'TÜRKÇE-örnek.com', 'www.', 'a.b-c.de:81'] as $domain) {
            $this->assertSame(
                \Veribenim\VeribenimConfig::cleanDomainForFilename($domain),
                Veribenim_Frontend::clean_domain_for_filename($domain),
                "Core SDK ile WordPress eklentisi '{$domain}' için farklı bundle adı üretiyor"
            );
        }
    }

    public function test_enqueue_outputs_nothing_without_token(): void
    {
        (new Veribenim_Frontend())->enqueue_script();

        $this->assertSame([], VeribenimWpTestState::$enqueuedScripts);
    }

    public function test_enqueue_outputs_nothing_in_admin(): void
    {
        VeribenimWpTestState::$options['veribenim_token'] = str_repeat('a', 32);
        VeribenimWpTestState::$isAdmin = true;

        (new Veribenim_Frontend())->enqueue_script();

        $this->assertSame([], VeribenimWpTestState::$enqueuedScripts);
    }

    public function test_enqueue_derives_bundle_url_from_domain_option(): void
    {
        VeribenimWpTestState::$options['veribenim_token']  = str_repeat('a', 32);
        VeribenimWpTestState::$options['veribenim_domain'] = 'https://www.claude.com';

        (new Veribenim_Frontend())->enqueue_script();

        $script = VeribenimWpTestState::$enqueuedScripts[0] ?? [];
        $this->assertSame('veribenim-banner', $script['handle'] ?? null);
        $this->assertSame('https://bundles.veribenim.com/claudecom.js', $script['src'] ?? null);
        $this->assertFalse($script['in_footer'] ?? null);
    }

    public function test_enqueue_falls_back_to_home_url(): void
    {
        VeribenimWpTestState::$options['veribenim_token'] = str_repeat('a', 32);
        VeribenimWpTestState::$homeUrl = 'https://www.mysite.example';

        (new Veribenim_Frontend())->enqueue_script();

        $this->assertSame(
            'https://bundles.veribenim.com/mysiteexample.js',
            VeribenimWpTestState::$enqueuedScripts[0]['src'] ?? null
        );
    }

    public function test_enqueue_uses_manual_script_url_override(): void
    {
        VeribenimWpTestState::$options['veribenim_token']      = str_repeat('a', 32);
        VeribenimWpTestState::$options['veribenim_script_url'] = 'https://cdn.example.com/ozel.js';

        (new Veribenim_Frontend())->enqueue_script();

        $this->assertSame(
            'https://cdn.example.com/ozel.js',
            VeribenimWpTestState::$enqueuedScripts[0]['src'] ?? null
        );
    }

    public function test_enqueue_ignores_default_placeholder_script_url(): void
    {
        VeribenimWpTestState::$options['veribenim_token']      = str_repeat('a', 32);
        VeribenimWpTestState::$options['veribenim_script_url'] = 'https://bundles.veribenim.com/bundle.js';
        VeribenimWpTestState::$options['veribenim_domain']     = 'claude.com';

        (new Veribenim_Frontend())->enqueue_script();

        // Placeholder yok sayılır, domain'den türetilir
        $this->assertSame(
            'https://bundles.veribenim.com/claudecom.js',
            VeribenimWpTestState::$enqueuedScripts[0]['src'] ?? null
        );
    }

    public function test_add_script_attributes_injects_token_and_flags(): void
    {
        VeribenimWpTestState::$options['veribenim_token'] = str_repeat('a', 32);

        $tag = "<script src='https://bundles.veribenim.com/claudecom.js' id='veribenim-banner-js'></script>\n";
        $out = (new Veribenim_Frontend())->add_script_attributes($tag, 'veribenim-banner');

        $this->assertStringContainsString('async defer', $out);
        $this->assertStringContainsString('data-veribenim="' . str_repeat('a', 32) . '"', $out);
        $this->assertStringContainsString("src='https://bundles.veribenim.com/claudecom.js'", $out);
    }

    public function test_add_script_attributes_ignores_other_handles(): void
    {
        $tag = "<script src='https://example.com/other.js' id='other-js'></script>\n";
        $out = (new Veribenim_Frontend())->add_script_attributes($tag, 'other');

        $this->assertSame($tag, $out);
    }
}
