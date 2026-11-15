<?php

declare(strict_types=1);

namespace Veribenim\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Veribenim\Proxy\ConsentProxy;
use Veribenim\Proxy\ProxyTransport;
use Veribenim\VeribenimClient;
use Veribenim\VeribenimConfig;

class VeribenimServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/veribenim.php', 'veribenim');

        $this->app->singleton(VeribenimConfig::class, function () {
            return new VeribenimConfig(
                token:         (string) config('veribenim.token'),
                domain:        (string) config('veribenim.domain', ''),
                lang:          (string) config('veribenim.lang'),
                timeout:       (int) config('veribenim.timeout'),
                debug:         (bool) config('veribenim.debug'),
                apiUrl:        (string) (config('veribenim.api_url') ?: VeribenimConfig::DEFAULT_API_URL),
                bundleBaseUrl: (string) (config('veribenim.bundle_base_url') ?: VeribenimConfig::DEFAULT_BUNDLE_BASE_URL),
                webUrl:        (string) (config('veribenim.web_url') ?: VeribenimConfig::DEFAULT_WEB_URL),
            );
        });

        $this->app->singleton(VeribenimClient::class, function ($app) {
            return new VeribenimClient($app->make(VeribenimConfig::class));
        });

        // @ifConsented direktifi ve programatik rıza kontrolü için
        $this->app->singleton(ConsentHelper::class, function ($app) {
            return new ConsentHelper($app->make(VeribenimClient::class));
        });

        // Alias: app('veribenim')
        $this->app->alias(VeribenimClient::class, 'veribenim');

        // Birinci taraf rıza proxy'si — anahtar yalnızca sunucu tarafında yaşar.
        $this->app->singleton(ConsentProxy::class, function ($app) {
            return ConsentProxy::fromConfig(
                $app->make(VeribenimConfig::class),
                (string) config('veribenim.consent_proxy.proxy_key'),
                $app->bound(ProxyTransport::class) ? $app->make(ProxyTransport::class) : null,
            );
        });
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

        $this->registerConsentProxyRoutes();
    }

    /**
     * /_vb/{token}/c ve /_vb/{token}/ping — yalnızca açıkça etkinleştirildiğinde.
     *
     * Rotalar BİLEREK 'web' grubunun dışında: oturum çerezi açılmaz, CSRF
     * (bundle'ın text/plain POST'u) uygulanmaz, çerezler şifrelenmez — rıza
     * çerezi Veribenim'in yazdığı hâliyle kalmalı. Token deseni dar tutulur;
     * asıl eşleşme ConsentProxy içinde yapılandırılmış token'la birebirdir.
     */
    private function registerConsentProxyRoutes(): void
    {
        if (! config('veribenim.consent_proxy.enabled')
            || (string) config('veribenim.consent_proxy.proxy_key') === ''
            || (string) config('veribenim.token') === '') {
            return;
        }

        $router = $this->app['router'];
        $router->post('_vb/{token}/c', Http\Controllers\ConsentProxyController::class)
            ->where('token', '[A-Za-z0-9]{16,64}')
            ->name('veribenim.consent-proxy.consent');
        $router->get('_vb/{token}/ping', Http\Controllers\ConsentProxyController::class)
            ->where('token', '[A-Za-z0-9]{16,64}')
            ->name('veribenim.consent-proxy.ping');
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

            // @veribenimForm('iletisim-formu')
            // Formu sunucu taraflı HTML olarak render eder.
            // Opsiyonel ikinci parametre: ['class' => '...', 'id' => '...']
            $blade->directive('veribenimForm', function (string $expression) {
                // expression: 'slug' veya 'slug', ['class' => 'my-form']
                return "<?php echo app(\Veribenim\VeribenimClient::class)->renderFormHtml({$expression}); ?>";
            });
        });
    }
}
