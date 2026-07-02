<?php

declare(strict_types=1);

namespace Veribenim\Laravel\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Veribenim\Laravel\VeribenimFacade;
use Veribenim\Laravel\VeribenimServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected const TOKEN = 'laravel-testtoken-1234567890abcd';

    protected function getPackageProviders($app): array
    {
        return [VeribenimServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Veribenim' => VeribenimFacade::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('veribenim.token', self::TOKEN);
        $app['config']->set('veribenim.domain', 'claude.com');
        $app['config']->set('veribenim.lang', 'en');
        $app['config']->set('veribenim.timeout', 3);
        $app['config']->set('veribenim.debug', false);
    }
}
