# Veribenim PHP SDK

> KVKK & GDPR uyumlu çerez yönetimi için PHP / Laravel / WordPress SDK monoreposu.

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![Packagist](https://img.shields.io/packagist/v/veribenim/php-sdk)](https://packagist.org/packages/veribenim/php-sdk)
[![PHP](https://img.shields.io/packagist/php-v/veribenim/php-sdk)](https://packagist.org/packages/veribenim/php-sdk)

---

## Paketler

| Paket | Açıklama |
|---|---|
| [`veribenim/php-sdk`](#veribenimphp-sdk) | Framework-agnostic çekirdek — tüm paketlerin temeli |
| [`veribenim/laravel`](#veribenimlaravel) | Laravel entegrasyonu (ServiceProvider, Facade, Middleware, Blade) |
| [`veribenim/wordpress`](#veribenimwordpress) | WordPress plugin — tek tıkla kur, otomatik script injection |

---

## veribenim/php-sdk

Framework bağımsız PHP çekirdeği. Laravel dışı projelerde (Slim, Symfony, düz PHP vb.) kullanılır.

### Gereksinimler

- PHP 8.1+
- `ext-json`
- `ext-curl` (opsiyonel, yoksa `file_get_contents` fallback'i devreye girer)

### Kurulum

```bash
composer require veribenim/php-sdk
```

### İki Kullanım Yolu

SDK'yı iki farklı amaçla kullanabilirsiniz.

**Sadece banner göstermek istiyorsanız** — SDK kurulumu gerekmez. Panelden bundle URL'nizi kopyalayın (Siteniz → Entegrasyon) ve layout dosyanıza yapıştırın:

```html
<!-- layout.php veya header.php -->
<script src="https://bundles.veribenim.com/sitenizin-adı.js" async></script>
```

**Form rızası, DSAR veya diğer API işlemleri için** SDK'yı kurun. Token'ı Veribenim panelinden alın: Siteniz → Entegrasyon → Token.

```php
use Veribenim\VeribenimClient;

// Token ile başlat
$client = new VeribenimClient('ENV_TOKEN_32_CHAR');
```

### Tercih Yönetimi

```php
// Ziyaretçi tercihlerini oku (session ID cookie'den alınabilir)
$sessionId = $_COOKIE['veribenim_session'] ?? null;
$prefs = $client->getPreferences($sessionId);

if ($prefs && $prefs['preferences']['analytics']) {
    // Analytics aktif
}

// Tercihleri kaydet
$result = $client->savePreferences(
    preferences: [
        'necessary'   => true,
        'analytics'   => true,
        'marketing'   => false,
        'preferences' => true,
    ],
    sessionId: $sessionId
);
```

### Loglama

```php
// Sayfa görüntüleme logla
$client->logImpression();

// Onay kararı logla
$client->logConsent(
    action: 'accept_all',
    preferences: ['necessary' => true, 'analytics' => true, 'marketing' => true, 'preferences' => true]
);

// Desteklenen action değerleri:
// 'accept_all' | 'reject_all' | 'save_preferences' | 'ping' | 'visit' | 'exit'
```

### Form Rızası Takibi

İletişim formu, üyelik formu, bülten gibi kendi formlarınızdaki KVKK onayını Veribenim'e bildirin:

```php
// Form submit işlenirken:
$result = $client->logFormConsent(
    formName:    'contact',
    consented:   (bool) $_POST['kvkk_consent'],
    consentText: 'KVKK kapsamında kişisel verilerimin işlenmesini onaylıyorum.',
    metadata:    [
        'page'    => $_SERVER['HTTP_REFERER'] ?? '',
        'form_id' => 'contact-form',
        'email'   => $_POST['email'] ?? '',
    ]
);

// $result içeriği:
// ['id' => '...', 'form_name' => 'contact', 'consented' => true, 'created_at' => '...']
```

### DSAR Başvurusu

Ziyaretçilerin veri haklarını kullanabilmesi için başvuru formu entegrasyonu:

```php
$result = $client->submitDsar(
    requestType:  'erasure',      // Silme hakkı
    fullName:     'Ahmet Yılmaz',
    email:        'ahmet@example.com',
    description:  'Tüm kişisel verilerimin silinmesini talep ediyorum.'
);

// $result içeriği:
// ['id' => '...', 'status' => 'pending', 'deadline' => '2026-04-30T...', ...]

// Desteklenen request_type değerleri:
// 'access'        — Verilerime erişim
// 'rectification' — Düzeltme
// 'erasure'       — Silme (unutulma hakkı)
// 'restriction'   — İşleme kısıtlama
// 'portability'   — Taşınabilirlik
// 'objection'     — İtiraz
// 'automated'     — Otomatik karar itirazı
```

---

## veribenim/laravel

### Gereksinimler

- PHP 8.1+
- Laravel 10, 11 veya 12

### Kurulum

```bash
composer require veribenim/laravel
```

ServiceProvider ve Facade otomatik keşfedilir (`extra.laravel` ile). Sonra:

```bash
php artisan vendor:publish --tag=veribenim-config
```

### Konfigürasyon (.env)

```env
VERIBENIM_TOKEN=env_token_32_char
VERIBENIM_LANG=tr
VERIBENIM_DEBUG=false
```

### Blade Kullanımı

```blade
{{-- Layout dosyanızda <head> içine ekleyin --}}
@veribenimScript

{{-- Belirli kategorilere rıza verilmişse render et --}}
@ifConsented('analytics')
    <x-google-analytics />
@endIfConsented
```

### Facade

```php
use Veribenim\Laravel\VeribenimFacade as Veribenim;

// Script tag
echo Veribenim::scriptTag();

// Tercih yönetimi
$prefs = Veribenim::getPreferences($sessionId);
Veribenim::savePreferences(['necessary' => true, 'analytics' => true, ...], $sessionId);

// Form rızası
Veribenim::logFormConsent(
    formName: 'newsletter',
    consented: $request->boolean('kvkk'),
    consentText: 'E-posta listesine katılmayı ve KVKK metnini onaylıyorum.',
    metadata: ['email' => $request->email]
);

// DSAR
Veribenim::submitDsar(
    requestType: 'access',
    fullName: $request->full_name,
    email: $request->email,
    description: $request->description
);
```

### Dependency Injection

```php
use Veribenim\VeribenimClient;

class ContactController extends Controller
{
    public function __construct(private readonly VeribenimClient $veribenim) {}

    public function store(Request $request): RedirectResponse
    {
        $this->veribenim->logFormConsent(
            formName:    'contact',
            consented:   $request->boolean('kvkk'),
            consentText: 'İletişim formu KVKK onayı',
            metadata:    ['email' => $request->email]
        );

        // ... diğer işlemler
    }
}
```

### Middleware

Belirli rotalara rıza zorunluluğu ekleyin:

```php
// routes/web.php
Route::middleware('veribenim.consent:analytics')->group(function () {
    Route::get('/dashboard', DashboardController::class);
});

// Birden fazla kategori:
Route::middleware('veribenim.consent:analytics,marketing')->group(function () {
    // ...
});
```

### DSAR Controller Örneği

```php
use Veribenim\VeribenimClient;

class DsarController extends Controller
{
    public function store(Request $request, VeribenimClient $veribenim): JsonResponse
    {
        $validated = $request->validate([
            'request_type' => 'required|in:access,rectification,erasure,restriction,portability,objection,automated',
            'full_name'    => 'required|string|max:255',
            'email'        => 'required|email',
            'description'  => 'nullable|string|max:2000',
        ]);

        $result = $veribenim->submitDsar(
            requestType: $validated['request_type'],
            fullName:    $validated['full_name'],
            email:       $validated['email'],
            description: $validated['description'] ?? ''
        );

        return response()->json([
            'message'  => 'Başvurunuz alındı. 30 gün içinde yanıtlanacaktır.',
            'deadline' => $result['deadline'] ?? null,
        ]);
    }
}
```

---

## veribenim/wordpress

### Kurulum

**Önerilen:** WordPress yönetici paneli > Eklentiler > Yeni Ekle > "Veribenim" araması.

**Manuel:** Bu repodan `packages/wordpress/` klasörünü `wp-content/plugins/veribenim/` altına kopyalayın.

### Kurulum Sonrası

1. Ayarlar > Veribenim sayfasına gidin
2. Veribenim panelinden aldığınız **Site Token**'ı girin
3. Dil tercihini seçin (Türkçe / İngilizce)
4. Kaydet — script tüm sayfalara otomatik eklenir

### Özellikler

- Otomatik script injection — her sayfaya `<head>` içinde eklenir
- Admin panel entegrasyonu — token ve dil ayarları tek sayfadan
- Multisite uyumlu
- WooCommerce çakışması yok
- Tema bağımsız

---

## Lisans

MIT © [Pariette](https://veribenim.com)
