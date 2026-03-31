# veribenim/php-sdk

> Veribenim KVKK & GDPR çerez onayı SDK — PHP

[![Packagist](https://img.shields.io/packagist/v/veribenim/php-sdk)](https://packagist.org/packages/veribenim/php-sdk)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/packagist/php-v/veribenim/php-sdk)](https://packagist.org/packages/veribenim/php-sdk)

## Kurulum

```bash
composer require veribenim/php-sdk
```

## İki Kullanım Yolu

**Sadece banner göstermek istiyorsanız** — SDK'ya gerek yok. Panelden bundle URL'nizi kopyalayın:

```html
<!-- layout.php veya header.php <head> içine -->
<script src="https://bundles.veribenim.com/siteadiniz.js" async></script>
```

**Form rızası, DSAR veya diğer API işlemleri için** SDK'yı kullanın. Token'ı [Veribenim Paneli](https://app.veribenim.com)'nden alın: Siteniz → Entegrasyon.

## Kullanım

```php
use Veribenim\VeribenimClient;

$client = new VeribenimClient('BURAYA_TOKEN_YAPISTIRIN');
```

## Form Rızası Takibi

```php
$client->logFormConsent(
    formName:    'contact',
    consented:   (bool) $_POST['kvkk'],
    consentText: 'KVKK kapsamında verilerimin işlenmesini onaylıyorum.',
    metadata:    ['email' => $_POST['email'] ?? '']
);
```

## DSAR (Veri Sahibi Başvurusu)

```php
$client->submitDsar(
    requestType: 'erasure',
    fullName:    'Ad Soyad',
    email:       'kullanici@example.com',
    description: 'Tüm verilerimin silinmesini talep ediyorum.'
);
```

## Tercih Yönetimi

```php
$prefs = $client->getPreferences();

$client->savePreferences([
    'necessary'   => true,
    'analytics'   => true,
    'marketing'   => false,
    'preferences' => true,
]);
```

## Gereksinimler

- PHP 8.1+
- `ext-json`
- `ext-curl` (opsiyonel, yoksa `file_get_contents` devreye girer)

## Framework Paketleri

| Paket | Açıklama |
|---|---|
| [`veribenim/laravel`](https://packagist.org/packages/veribenim/laravel) | Laravel ServiceProvider, Facade, Blade direktifleri |
| [`veribenim/wordpress`](https://packagist.org/packages/veribenim/wordpress) | WordPress plugin |

## Lisans

MIT © [Pariette](https://veribenim.com)
