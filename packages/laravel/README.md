# veribenim/laravel

> Veribenim KVKK & GDPR çerez onayı SDK — Laravel

[![Packagist](https://img.shields.io/packagist/v/veribenim/laravel)](https://packagist.org/packages/veribenim/laravel)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

## Kurulum

```bash
composer require veribenim/laravel
php artisan vendor:publish --tag=veribenim-config
```

ServiceProvider ve Facade otomatik keşfedilir.

## Konfigürasyon (.env)

```env
VERIBENIM_TOKEN=buraya_token_yapistirin
VERIBENIM_LANG=tr
```

Token'ı [Veribenim Paneli](https://app.veribenim.com)'nden alın: Siteniz → Entegrasyon.

## Banner Script

Layout blade dosyanızda:

```blade
<head>
    @veribenimScript
</head>
```

## Form Rızası (Facade)

```php
use Veribenim\Laravel\VeribenimFacade as Veribenim;

public function store(Request $request)
{
    Veribenim::logFormConsent(
        formName:    'contact',
        consented:   $request->boolean('kvkk'),
        consentText: 'KVKK kapsamında verilerimin işlenmesini onaylıyorum.',
        metadata:    ['email' => $request->email]
    );
}
```

## DSAR

```php
Veribenim::submitDsar(
    requestType: 'erasure',
    fullName:    $request->full_name,
    email:       $request->email,
    description: $request->description
);
```

## Dependency Injection

```php
use Veribenim\VeribenimClient;

class ContactController extends Controller
{
    public function __construct(private readonly VeribenimClient $veribenim) {}

    public function store(Request $request)
    {
        $this->veribenim->logFormConsent(
            formName:  'contact',
            consented: $request->boolean('kvkk'),
        );
    }
}
```

## Blade Direktifleri

```blade
@ifConsented('analytics')
    <x-google-analytics />
@endIfConsented
```

## Middleware

```php
Route::middleware('veribenim.consent:analytics')->group(function () {
    Route::get('/dashboard', DashboardController::class);
});
```

## Gereksinimler

- PHP 8.1+
- Laravel 10, 11 veya 12

## Lisans

MIT © [Pariette](https://veribenim.com)
