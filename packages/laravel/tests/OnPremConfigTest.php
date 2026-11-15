<?php

declare(strict_types=1);

namespace Veribenim\Laravel\Tests;

use Veribenim\VeribenimClient;
use Veribenim\VeribenimConfig;

class OnPremConfigTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('veribenim.api_url', 'https://api.kurum.local');
        $app['config']->set('veribenim.bundle_base_url', 'https://cdn.kurum.local/b');
        $app['config']->set('veribenim.web_url', 'https://panel.kurum.local');
    }

    public function test_on_prem_endpoints_flow_into_core_config(): void
    {
        $config = $this->app->make(VeribenimConfig::class);

        $this->assertSame('https://api.kurum.local', $config->apiUrl);
        $this->assertSame('https://panel.kurum.local', $config->webUrl);
        $this->assertSame(
            '<script src="https://cdn.kurum.local/b/claudecom.js" async></script>',
            $this->app->make(VeribenimClient::class)->scriptTag()
        );
    }
}
