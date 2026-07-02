<?php

declare(strict_types=1);

namespace Veribenim\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Veribenim\VeribenimConfig;

class VeribenimConfigTest extends TestCase
{
    public function test_empty_token_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('token boş olamaz');

        new VeribenimConfig(token: '');
    }

    public function test_defaults(): void
    {
        $config = new VeribenimConfig(token: 'abcdef1234567890abcdef1234567890');

        $this->assertSame('abcdef1234567890abcdef1234567890', $config->token);
        $this->assertSame('', $config->domain);
        $this->assertSame('tr', $config->lang);
        $this->assertSame(5, $config->timeout);
        $this->assertFalse($config->debug);
        $this->assertSame('https://live.veribenim.com', $config->apiUrl);
        $this->assertSame('', $config->scriptUrl);
    }

    public function test_custom_values(): void
    {
        $config = new VeribenimConfig(
            token: 'tok',
            domain: 'claude.com',
            lang: 'en',
            timeout: 10,
            debug: true,
            apiUrl: 'https://staging.veribenim.com',
        );

        $this->assertSame('claude.com', $config->domain);
        $this->assertSame('en', $config->lang);
        $this->assertSame(10, $config->timeout);
        $this->assertTrue($config->debug);
        $this->assertSame('https://staging.veribenim.com', $config->apiUrl);
    }

    /**
     * Backend CookieBundleService::cleanDomainForFilename() ile aynı mantık —
     * edge case davranışları bilinçli olarak sabitlenir (regresyon koruması).
     */
    #[DataProvider('domainProvider')]
    public function test_clean_domain_for_filename(string $input, string $expected): void
    {
        $this->assertSame($expected, VeribenimConfig::cleanDomainForFilename($input));
    }

    public static function domainProvider(): array
    {
        return [
            'düz domain'            => ['claude.com', 'claudecom'],
            'https protokol'        => ['https://claude.com', 'claudecom'],
            'http + www'            => ['http://www.claude.com', 'claudecom'],
            'path ve query'         => ['https://www.example.com/path/page?x=1', 'examplecompathpagex1'],
            'port'                  => ['example.com:8080', 'examplecom8080'],
            'subdomain'             => ['sub.domain.example.com', 'subdomainexamplecom'],
            'büyük harf'            => ['EXAMPLE.COM', 'examplecom'],
            // www yalnızca küçük harfle strip edilir (backend ile aynı davranış)
            'büyük harf www'        => ['WWW.CLAUDE.COM', 'wwwclaudecom'],
            'tire'                  => ['my-site.co.uk', 'mysitecouk'],
            'unicode karakterler'   => ['türkçe-örnek.com', 'trkernekcom'],
            'boş string fallback'   => ['', 'bundle'],
            'sadece www fallback'   => ['www.', 'bundle'],
        ];
    }

    public function test_bundle_url_prefers_script_url_override(): void
    {
        $config = new VeribenimConfig(
            token: 'tok',
            domain: 'claude.com',
            scriptUrl: 'https://cdn.example.com/custom.js',
        );

        $this->assertSame('https://cdn.example.com/custom.js', $config->getBundleUrl());
    }

    public function test_bundle_url_derived_from_domain(): void
    {
        $config = new VeribenimConfig(token: 'tok', domain: 'https://www.claude.com');

        $this->assertSame('https://bundles.veribenim.com/claudecom.js', $config->getBundleUrl());
    }

    public function test_bundle_url_empty_without_domain_and_script_url(): void
    {
        $config = new VeribenimConfig(token: 'tok');

        $this->assertSame('', $config->getBundleUrl());
    }
}
