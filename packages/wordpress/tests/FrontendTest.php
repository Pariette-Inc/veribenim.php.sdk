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

    public function test_inject_script_outputs_nothing_without_token(): void
    {
        $this->assertSame('', $this->captureInjectScript());
    }

    public function test_inject_script_outputs_nothing_in_admin(): void
    {
        VeribenimWpTestState::$options['veribenim_token'] = str_repeat('a', 32);
        VeribenimWpTestState::$isAdmin = true;

        $this->assertSame('', $this->captureInjectScript());
    }

    public function test_inject_script_derives_bundle_url_from_domain_option(): void
    {
        VeribenimWpTestState::$options['veribenim_token']  = str_repeat('a', 32);
        VeribenimWpTestState::$options['veribenim_domain'] = 'https://www.claude.com';

        $output = $this->captureInjectScript();

        $this->assertStringContainsString('src="https://bundles.veribenim.com/claudecom.js"', $output);
        $this->assertStringContainsString('async defer', $output);
        $this->assertStringContainsString('data-veribenim="' . str_repeat('a', 32) . '"', $output);
    }

    public function test_inject_script_falls_back_to_home_url(): void
    {
        VeribenimWpTestState::$options['veribenim_token'] = str_repeat('a', 32);
        VeribenimWpTestState::$homeUrl = 'https://www.mysite.example';

        $output = $this->captureInjectScript();

        $this->assertStringContainsString('src="https://bundles.veribenim.com/mysiteexample.js"', $output);
    }

    public function test_inject_script_uses_manual_script_url_override(): void
    {
        VeribenimWpTestState::$options['veribenim_token']      = str_repeat('a', 32);
        VeribenimWpTestState::$options['veribenim_script_url'] = 'https://cdn.example.com/ozel.js';

        $output = $this->captureInjectScript();

        $this->assertStringContainsString('src="https://cdn.example.com/ozel.js"', $output);
    }

    public function test_inject_script_ignores_default_placeholder_script_url(): void
    {
        VeribenimWpTestState::$options['veribenim_token']      = str_repeat('a', 32);
        VeribenimWpTestState::$options['veribenim_script_url'] = 'https://bundles.veribenim.com/bundle.js';
        VeribenimWpTestState::$options['veribenim_domain']     = 'claude.com';

        $output = $this->captureInjectScript();

        // Placeholder yok sayılır, domain'den türetilir
        $this->assertStringContainsString('src="https://bundles.veribenim.com/claudecom.js"', $output);
    }

    private function captureInjectScript(): string
    {
        ob_start();
        (new Veribenim_Frontend())->inject_script();

        return (string) ob_get_clean();
    }
}
