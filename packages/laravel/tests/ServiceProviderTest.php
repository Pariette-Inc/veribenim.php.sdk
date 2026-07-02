<?php

declare(strict_types=1);

namespace Veribenim\Laravel\Tests;

use Illuminate\Support\Facades\Blade;
use Veribenim\Laravel\ConsentHelper;
use Veribenim\Laravel\Http\Middleware\VerifyConsent;
use Veribenim\Laravel\VeribenimFacade;
use Veribenim\VeribenimClient;
use Veribenim\VeribenimConfig;

class ServiceProviderTest extends TestCase
{
    public function test_config_is_merged_with_defaults(): void
    {
        $this->assertSame('/', config('veribenim.middleware.redirect_to'));
        $this->assertSame(['strictly_necessary'], config('veribenim.middleware.required_categories'));
    }

    public function test_veribenim_config_singleton_reads_app_config(): void
    {
        $config = $this->app->make(VeribenimConfig::class);

        $this->assertSame(self::TOKEN, $config->token);
        $this->assertSame('claude.com', $config->domain);
        $this->assertSame('en', $config->lang);
        $this->assertSame(3, $config->timeout);
        $this->assertFalse($config->debug);
        $this->assertSame($config, $this->app->make(VeribenimConfig::class));
    }

    public function test_client_singleton_and_alias_resolve_to_same_instance(): void
    {
        $client = $this->app->make(VeribenimClient::class);

        $this->assertInstanceOf(VeribenimClient::class, $client);
        $this->assertSame($client, $this->app->make('veribenim'));
        $this->assertSame($client, $this->app->make(VeribenimClient::class));
    }

    public function test_consent_helper_is_registered_as_singleton(): void
    {
        $helper = $this->app->make(ConsentHelper::class);

        $this->assertInstanceOf(ConsentHelper::class, $helper);
        $this->assertSame($helper, $this->app->make(ConsentHelper::class));
    }

    public function test_middleware_alias_is_registered(): void
    {
        $aliases = $this->app['router']->getMiddleware();

        $this->assertArrayHasKey('veribenim.consent', $aliases);
        $this->assertSame(VerifyConsent::class, $aliases['veribenim.consent']);
    }

    public function test_facade_resolves_client_and_renders_script_tag(): void
    {
        $this->assertSame(
            '<script src="https://bundles.veribenim.com/claudecom.js" async></script>',
            VeribenimFacade::scriptTag()
        );
    }

    // -------------------------------------------------------------------------
    // Blade direktifleri
    // -------------------------------------------------------------------------

    public function test_veribenim_script_directive_compiles(): void
    {
        $compiled = Blade::compileString('@veribenimScript');

        $this->assertStringContainsString('VeribenimClient', $compiled);
        $this->assertStringContainsString('scriptTag()', $compiled);
    }

    public function test_if_consented_directives_compile(): void
    {
        $compiled = Blade::compileString("@ifConsented('analytics') X @endIfConsented");

        $this->assertStringContainsString("isConsented('analytics')", $compiled);
        $this->assertStringContainsString('<?php if(', $compiled);
        $this->assertStringContainsString('<?php endif; ?>', $compiled);
    }

    public function test_veribenim_form_directive_compiles(): void
    {
        $compiled = Blade::compileString("@veribenimForm('iletisim-formu', ['class' => 'my-form'])");

        $this->assertStringContainsString("renderFormHtml('iletisim-formu', ['class' => 'my-form'])", $compiled);
    }

    public function test_rendered_blade_view_outputs_script_tag(): void
    {
        $html = Blade::render('@veribenimScript');

        $this->assertSame(
            '<script src="https://bundles.veribenim.com/claudecom.js" async></script>',
            $html
        );
    }
}
