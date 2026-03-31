<?php

declare(strict_types=1);

namespace Veribenim\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Veribenim\VeribenimClient;
use Veribenim\VeribenimConfig;

class VeribenimServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/veribenim.php', 'veribenim');

        $this->app->singleton(VeribenimConfig::class, function () {
            return new VeribenimConfig(
                token:   config('veribenim.token'),
                lang:    config('veribenim.lang'),
                timeout: (int) config('veribenim.timeout'),
                debug:   (bool) config('veribenim.debug'),
            );
        });

        $this->app->singleton(VeribenimClient::class, function ($app) {
            return new VeribenimClient($app->make(VeribenimConfig::class));
        });

        // Alias: app('veribenim')
        $this->app->alias(VeribenimClient::class, 'veribenim');
    }

    public function boot(): void
    {
        // Config publish
        $this->publishes([
            __DIR__ . '/../config/veribenim.php' => config_path('veribenim.php'),
        ], 'veribenim-config');

        // Views publish
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/veribenim'),
        ], 'veribenim-views');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'veribenim');

        // Blade direktifleri
        $this->registerBladeDirectives();

        // Middleware alias
        $router = $this->app['router'];
        $router->aliasMiddleware('veribenim.consent', Http\Middleware\VerifyConsent::class);
    }

    private function registerBladeDirectives(): void
    {
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade) {
            // @veribenimScript — banner scriptini sayfaya ekler
            $blade->directive('veribenimScript', function () {
                return '<?php echo app(\Veribenim\VeribenimClient::class)->scriptTag(); ?>';
            });

            // @ifConsented('analytics') ... @endIfConsented
            $blade->directive('ifConsented', function (string $expression) {
                return "<?php if(app(\Veribenim\Laravel\ConsentHelper::class)->isConsented({$expression})): ?>";
            });
            $blade->directive('endIfConsented', function () {
                return '<?php endif; ?>';
            });
        });
    }
}
