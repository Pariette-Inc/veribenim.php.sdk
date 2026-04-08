# Veribenim PHP SDK

**KVKK & GDPR Uyumlu Veri Koruma ve Çerez Yönetimi SDK'sı**

[![Packagist Version](https://img.shields.io/packagist/v/veribenim/php-sdk.svg?style=flat-square)](https://packagist.org/packages/veribenim/php-sdk)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-blue?style=flat-square)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)
[![Downloads](https://img.shields.io/packagist/dm/veribenim/php-sdk.svg?style=flat-square)](https://packagist.org/packages/veribenim/php-sdk)

Veribenim PHP SDK, **framework'ten bağımsız, saf PHP ile entegrasyonu sağlayan, Türkiye'nin en kapsamlı KVKK ve GDPR uyumlu veri güvenliği çözümüdür**. Tüm PHP tabanlı uygulamalarda (Laravel, Symfony, WordPress, kendi yazılımınız) kişisel veri işlemesini kontrol altına alın.

**Privacy by Design prensiplerine dayanan, standalone kurulumdan WordPress eklentilerine kadar, tüm PHP ekosisteminde çalışan kurumsal seviye Consent Management Platform (CMP).**

---

## İçindekiler

- [Neden Veribenim?](#neden-veribenim)
- [Hızlı Başlangıç](#hızlı-başlangıç)
- [Nasıl Kurulur?](#nasıl-kurulur)
- [Kullanım](#kullanım)
  - [VeribenimClient Sınıfı](#veribenimclient-sınıfı)
  - [Çerez Rızası Takibi](#çerez-rızası-takibi)
  - [DSAR İşlemleri](#dsar-işlemleri)
  - [Çerez Banner Entegrasyonu](#çerez-banner-entegrasyonu)
- [Güvenlik Standartları](#güvenlik-standartları)
- [Gereksinimler](#gereksinimler)
- [Lisans](#lisans)

---

## Neden Veribenim?

### ✅ Framework'ten Bağımsız KVKK Çözümü

Veribenim PHP SDK, **sadece Laravel değil** — tüm PHP uygulamalarında kullanılabilir:

- **Vanilla PHP**: Raw PHP uygulamalarında doğrudan entegrasyon
- **WordPress**: Plugin olarak hazır, veya SDK olarak
- **Symfony**: Standalone SDK kullanımı
- **Statik siteler**: SSG + API kombinasyonlarında
- **Legacy uygulamalar**: Eski PHP 8.1+ kodlarında

### 🔒 Kurumsal Güvenlik Standardları

- **End-to-End Şifreleme**: AES-256 (veriler sunucuda), TLS 1.3+ (transit)
- **Sıfır Bilgi Mimarisi**: Veribenim sunucuları kişisel veri saklamaz
- **GDPR Artikel 32**: Uygun güvenlik önlemleri (şifreleme, hashing, IP anonimleştirme)
- **Denetim İzleri**: Tüm işlemler güvenli şekilde kaydedilir

### 📊 Tam DSAR Desteği

**GDPR Artikel 15-22 kapsamında tüm Veri Sahibi Hakları:**

- Erişim hakkı (Right of Access)
- Unutulma hakkı (Right to be Forgotten)
- Düzeltme hakkı (Rectification)
- İşlemeyi kısıtlama (Restriction)
- Taşınabilirlik (Portability)
- İtiraz hakkı (Objection)
- Otomatik karar almaya karşı itiraz

### ⚡ Minimal Dependencies

- **Sadece 3 dosya**: VeribenimClient, Request, Response
- **Sadece ext-json ve ext-curl**: Hiç ağır framework ihtiyacı yok
- **Fallback mekanizması**: curl yoksa file_get_contents kullanır
- **~50KB**: Hafif ve hızlı

### 🚀 Yüksek Performans

- **Senkron API**: Bloke olmayan, eşzamanlı çağrılar
- **Request caching**: Aynı sorguları tekrar yapmaz
- **Batch operations**: Birden fazla işlemi tek istekte gönder
- **Minimal memory footprint**: Düşük sunucu kaynağı kullanımı

### 🏢 Uçtan Uca KVKK Yönetim Platformu

Veribenim sadece bir çerez SDK'sı değil, **tam kapsamlı bir KVKK/GDPR uyum yönetim platformu**dur:

| Modül | Açıklama |
|-------|----------|
| **Veri Envanteri** | KVKK Md.16 / GDPR Md.30: Departman ve süreç bazlı veri haritalama, 20 veri kategorisi, VERBİS uyumlu export |
| **Saklama-İmha Otomasyonu** | KVKK Md.7 / GDPR Md.17: Saklama politikaları, otomatik imha, imha tutanakları, 5 imha yöntemi |
| **Risk Yönetimi** | KVKK Md.12 / GDPR Md.35: 5x5 risk matrisi, 7 risk kategorisi, aksiyon takibi, risk raporu export |
| **İç Denetim & Aksiyon Takibi** | 6 denetim tipi, 0-100 puanlama, aksiyon atama ve gecikme takibi |
| **Doküman Şablonları** | 10 hazır KVKK/GDPR şablonu, değişken sistemi, çoklu dil, versiyon takibi |
| **Rıza Versiyonlama** | Onay metni versiyon takibi, yeniden onay mekanizması, versiyon karşılaştırma |
| **Veri Hakkı Talepleri (DSAR)** | KVKK Md.11 / GDPR Md.15-22: 7 talep tipi, otomatik 30 gün deadline, operatör dashboard |
| **Veri İhlali Yönetimi** | GDPR Md.33: 72 saat countdown, risk seviyesi, durum akışı, otorite bildirim kaydı |
| **VERBİS / RoPA Export** | KVKK VERBİS kaydı ve GDPR Md.30 RoPA: CSV/JSON export, 17 alan, otomatik haritalama |
| **Politika Yönetimi** | Gizlilik politikası, çerez politikası, KVKK aydınlatma, veri işleme sözleşmesi — çoklu dil, PDF/HTML export |
| **Uyumluluk Skoru** | 22 kural, 5 kategori, A-F notlandırma, adım adım düzeltme önerileri |
| **Form Rızası Takibi** | İletişim, üyelik, bülten formlarındaki KVKK onayını API ile kayıt altına alma |
| **Webhook Sistemi** | 7 olay tipi, HMAC-SHA256 imzalama, Slack/Teams/n8n entegrasyonu |
| **Çerez Tarayıcı** | 50+ bilinen tracker otomatik tespiti (GA, Meta Pixel, Hotjar vb.) |
| **Site Sağlık Kontrolü** | 7 faktörlü KVKK uyumluluk taraması |
| **Tercih Merkezi** | Ziyaretçilerin çerez tercihlerini her zaman değiştirebildiği kalıcı panel + DSAR entegrasyonu |
| **AI Asistan** | RAG tabanlı KVKK/GDPR bilgi asistanı |
| **Meşru Menfaat Değerlendirmesi (LIA)** | KVK Kurul rehberi uyumlu 3-adım balans testi: Amaç → Zorunluluk → Dengeleme |
| **VERBİS Kaydı** | KVKK Md.16: Veri işleme aktivitesi kayıt asistanı, exemption check, otomatik export |
| **Rıza Yenileme** | KVK Kurul Çerez Rehberi 2022: 12 aylık yenileme, `consent_renewal_required` API flag |
| **Rızayı Geri Çekme** | KVKK Md.11/1-e: `withdraw` aksiyonu, ispat yükümlülüğü için audit trail korunur |
| **Veri Saklama & İmha** | KVKK Md.7: Ortam bazlı saklama süreleri, otomatik periyodik imha |
| **Çerez Duvarı Koruması** | KVK Kurul kararı: Cookie wall yasağı, compliance score kontrolü (ağırlık 12) |

---

## Hızlı Başlangıç

```bash
composer require veribenim/php-sdk
```

```php
<?php
use Veribenim\VeribenimClient;

// SDK'yı başlat
$client = new VeribenimClient(
    token: 'your_api_token',
    domain: 'example.com', // Bundle script URL için (example.com → examplecom.js)
    lang: 'tr',
    debug: false
);

// Form rızası kaydet
$client->logFormConsent(
    formName: 'contact',
    consented: true,
    consentText: 'KVKK şartlarını kabul ettim',
    metadata: ['email' => 'user@example.com']
);

// Veri sahibi erişim isteği
$client->submitDsar(
    requestType: 'access',
    fullName: 'John Doe',
    email: 'john@example.com'
);
```

---

## Nasıl Kurulur?

### Ön Koşullar

- **PHP** 8.1 veya daha yeni
- **ext-curl** veya **ext-json** (standart yüklüdür)
- **Composer**
- Veribenim hesabı ([veribenim.com](https://veribenim.com))

### Kurulum Adımları

**1. Composer ile kütüphane yükle**

```bash
composer require veribenim/php-sdk
```

**2. VeribenimClient'i başlat**

```php
<?php
require_once 'vendor/autoload.php';

use Veribenim\VeribenimClient;

$client = new VeribenimClient(
    token: 'your_site_token_here',  // https://veribenim.com/dashboard
    domain: 'example.com',          // Bundle script URL (example.com → examplecom.js)
    lang: 'tr',                      // Dil seçimi
    debug: false                     // Production'da false
);
```

**3. Environment değişkenleri (opsiyonel)**

```php
// .env dosyanız varsa:
$client = new VeribenimClient(
    token: $_ENV['VERIBENIM_TOKEN'],
    domain: $_ENV['VERIBENIM_DOMAIN'] ?? '',
    lang: $_ENV['VERIBENIM_LANG'] ?? 'tr',
    debug: $_ENV['APP_DEBUG'] ?? false
);
```

### Framework Entegrasyonları

**Laravel'de (ServiceProvider)**

```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->singleton(VeribenimClient::class, function () {
        return new VeribenimClient(
            token: config('veribenim.token'),
            domain: config('veribenim.domain'),
            lang: config('veribenim.lang'),
            debug: config('app.debug')
        );
    });
}
```

**Symfony'de (Service)**

```yaml
# config/services.yaml
services:
    Veribenim\VeribenimClient:
        arguments:
            $token: '%env(VERIBENIM_TOKEN)%'
            $domain: '%env(VERIBENIM_DOMAIN)%'
            $lang: 'tr'
            $debug: '%kernel.debug%'
```

**WordPress'te (Plugin)**

```php
// wp-content/plugins/veribenim-consent/veribenim-consent.php
$veribenim = new VeribenimClient(
    token: get_option('veribenim_token'),
    lang: 'tr'
);

// Hook'lar ile entegre edin
add_action('wp_footer', function() use ($veribenim) {
    $veribenim->logImpression();
});
```

---

## Kullanım

### VeribenimClient Sınıfı

Ana sınıf, tüm işlemler burada gerçekleşir:

```php
use Veribenim\VeribenimClient;

$client = new VeribenimClient('TOKEN_HERE');

// Tüm metodlar:
$client->logFormConsent(...);      // Form rızası
$client->getPreferences(...);      // Tercih al
$client->savePreferences(...);     // Tercih kaydet
$client->logImpression();          // Sayfa ziyareti
$client->logConsent(...);          // Manuel rıza
$client->withdrawConsent(...);     // Rızayı geri çek (KVKK Md.11/1-e)
$client->submitDsar(...);          // DSAR isteği
```

### Çerez Rızası Takibi

#### logFormConsent — Form Gönderişi

Her form gönderişinde KVKK uyumluluğu sağla:

```php
try {
    $client->logFormConsent(
        formName: 'newsletter_signup',
        consented: true,
        consentText: 'Haber bültenine abone olmak için kişisel verilerinizin işlenmesine rıza veriyorum.',
        metadata: [
            'email' => 'user@example.com',
            'source' => 'homepage',
            'language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'tr'
        ]
    );
    
    // İşlem başarılı
    $response = json_encode(['status' => 'success']);
} catch (Exception $e) {
    // Hata yönetimi
    error_log('Veribenim error: ' . $e->getMessage());
}
```

**Parametreler:**

| Parametre | Tür | Açıklama |
|---|---|---|
| `formName` | string | Form tanımlayıcısı |
| `consented` | bool | Rıza durumu |
| `consentText` | string | Gösterilen metin |
| `metadata` | array | İsteğe bağlı ek veriler |

**Dönüş:**

```php
[
    'success' => true,
    'message' => 'Form consent logged',
    'consent_id' => '550e8400-e29b-41d4-a716-446655440000'
]
```

#### getPreferences — Kullanıcı Tercihlerini Alma

```php
// Oturumdan session ID'yi al
$sessionId = $_COOKIE['veribenim_session'] ?? null;

$preferences = $client->getPreferences($sessionId);

// Çıktı:
// [
//     'necessary' => true,
//     'analytics' => false,
//     'marketing' => true,
//     'preferences' => true,
// ]

if ($preferences['analytics']) {
    // Analytics kodu çalıştır
    echo "<script>gtag('config', 'GA_ID');</script>";
}
```

#### savePreferences — Tercihler Kaydet

```php
$client->savePreferences([
    'necessary' => true,    // Her zaman true
    'analytics' => true,
    'marketing' => false,
    'preferences' => true
]);

// AJAX ile
header('Content-Type: application/json');
echo json_encode(['status' => 'saved']);
```

#### logImpression — Sayfa Ziyareti

```php
// Her sayfada çalıştır
$client->logImpression();

// Veya Analytics entegrasyonu
if ($client->getPreferences()['analytics']) {
    $client->logImpression();
}
```

#### logConsent — Manuel Rıza Kaydı

```php
$client->logConsent([
    'categories' => ['analytics', 'marketing'],
    'language' => 'tr',
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'consent_timestamp' => date('c')
]);
```

### DSAR İşlemleri

#### submitDsar — Veri Sahibi Hakları

GDPR Artikel 15-22 kapsamında tüm hakları yerine getir:

```php
public function handleDataRequest() {
    $requestType = $_POST['request_type'];  // Validation yap!
    $fullName = $_POST['full_name'];
    $email = $_POST['email'];
    $description = $_POST['description'] ?? '';
    
    try {
        $response = $client->submitDsar(
            requestType: $requestType,
            fullName: $fullName,
            email: $email,
            description: $description
        );
        
        // E-mail kullanıcıya gönder
        mail($email, 'İsteğiniz kaydedildi', 
            'En kısa zamanda size dönüş yapılacaktır.');
            
        return ['success' => true, 'request_id' => $response['id']];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
```

**Desteklenen DSAR Türleri:**

| Tür | GDPR Madde | Açıklama |
|---|---|---|
| `access` | Artikel 15 | Erişim hakkı |
| `erasure` | Artikel 17 | Unutulma hakkı |
| `rectification` | Artikel 16 | Düzeltme hakkı |
| `restriction` | Artikel 18 | İşlemeyi kısıtlama |
| `portability` | Artikel 20 | Taşınabilirlik |
| `objection` | Artikel 21 | İtiraz hakkı |
| `automated` | Artikel 22 | Otomatik karar almaya itiraz |

**Dönüş:**

```php
[
    'success' => true,
    'request_id' => '550e8400-e29b-41d4-a716-446655440000',
    'status' => 'submitted',
    'estimated_completion' => '2026-04-30'
]
```

### withdrawConsent — Rızayı Geri Çekme (KVKK Md.11/1-e)

KVKK, rızayı geri çekmenin rıza vermek kadar kolay olmasını zorunlu kılar. Consent kaydı temizlenir, audit trail korunur:

```php
// Kullanıcı "Tüm izinleri geri çek" butonuna tıkladığında
$sessionId = $_COOKIE['veribenim_session'] ?? null;

try {
    $client->withdrawConsent($sessionId);
    // Başarılı — tüm consent verisi temizlendi, log kaydı tutuldu
    http_response_code(200);
    echo json_encode(['withdrawn' => true]);
} catch (Exception $e) {
    error_log('Withdraw error: ' . $e->getMessage());
}
```

### Rıza Yenileme Kontrolü

KVK Kurul Çerez Rehberi 2022 gereği rıza 12 ayda bir yenilenmelidir. API, süresi dolmak üzere olan rızalar için `consent_renewal_required: true` döndürür:

```php
// Sayfa yüklenirken kontrol et
$preferences = $client->getPreferences($sessionId);

if ($preferences['consent_renewal_required'] ?? false) {
    // Banner'ı yeniden göster
    // veya JavaScript'e flag ilet
    $response['show_renewal_banner'] = true;
}
```

### Çerez Banner Entegrasyonu

**Seçenek 1: Banner kullanmadan (sadece SDK)**

```html
<!-- Çerez banner'ı manuel HTML'de -->
<div id="cookie-banner">
    <p>Veribenim'i kullanmak için çerezleri kabul etmelisiniz.</p>
    <button onclick="saveCookiePreferences()">Kabul Et</button>
</div>

<script>
function saveCookiePreferences() {
    fetch('/api/save-preferences', {
        method: 'POST',
        body: JSON.stringify({
            necessary: true,
            analytics: document.getElementById('analytics-checkbox').checked,
            marketing: document.getElementById('marketing-checkbox').checked,
            preferences: true
        })
    });
}
</script>
```

**Seçenek 2: Veribenim Bundle'ı kullanarak (önerilen)**

```html
<!-- Başında çerez banner scriptini yükle -->
<script src="https://bundles.veribenim.com/your-site-token.js"></script>
```

Bu script otomatik olarak:
- Çerez banner'ını gösterir
- Kullanıcı tercihlerini yönetir
- SDK ile senkronize olur

---

## Güvenlik Standartları

### KVKK Uyumluluğu (Türkiye Veri Koruma Yasası)

Veribenim, VKK tarafından referans alınan standartlara uyar:

- **Açık Rıza**: Kullanıcılar kategoriye göre ayrı ayrı rıza verirler
- **Rıza Kaydı**: Her rıza, ret, geri çekilme loglanır
- **Veri İşleme Kaydı**: Denetim izinde tutulur
- **Veri Silme**: Rıza geri çekildiğinde 30 gün içinde silinir
- **Veri Sorumlusu**: Tam sorumluluk yönetimi

**Referans**: [Veri Koruma Kurulu](https://www.kvk.gov.tr/)

### GDPR Uyumluluğu (Avrupa Birliği)

- **Artikel 6** — Yasal dayanak (Rıza)
- **Artikel 7** — Rıza koşulları
- **Artikel 12-22** — Veri sahibi hakları
- **Artikel 25** — Privacy by Design
- **Artikel 32** — Güvenlik önlemleri
- **EDPB Kararları** — Çerez politikaları

### Veri Şifreleme

| Bileşen | Teknoloji | Standart |
|---|---|---|
| Veritabanı | AES-256-GCM | FIPS 140-2 |
| İletim | TLS 1.3+ | RFC 8446 |
| Anahtarlar | ECDH P-256 | NIST SP 800-56A |
| İmza | HMAC-SHA256 | RFC 2104 |

### IP Anonimleştirme

```
Gerçek IP:  192.168.1.42
Saklı IP:   192.168.0.0 (son octet silinir)

IPv6:       2001:db8::1
Saklı IP:   2001:db8::0 (son 64 bit silinir)
```

### Denetim İzleri

- Rıza verme/geri çekilme
- Form gönderişleri
- DSAR istekleri
- Veri silme işlemleri
- API çağrıları

Minimum **3 yıl** saklanır.

### Güvenlik Sertifikaları

- ✓ ISO 27001
- ✓ ISO 27018
- ✓ SOC 2 Type II
- ✓ GDPR Uyumlu
- ✓ KVKK Uyumlu
- ✓ Penetrasyon Testi (Yıllık)

---

## Gereksinimler

### Yazılım Gereksinimleri

```
PHP >= 8.1
ext-json (standart)
ext-curl (önerilen) veya ext-stream (fallback)
Composer
```

### Desteklenen Yapılar

| Platform | Sürüm | Notlar |
|---|---|---|
| PHP Vanilla | 8.1+ | Tam destek |
| Laravel | 10/11/12 | ServiceProvider ile |
| Symfony | 6.x+ | Dependency injection |
| WordPress | 6.0+ | Plugin veya SDK |
| Statik PHP | Any | Doğrudan entegrasyon |

### Üretim Konfigürasyonu

```php
$client = new VeribenimClient(
    token: getenv('VERIBENIM_TOKEN'),
    lang: 'tr',
    debug: false,  // Production'da MUTLAKA false
    timeout: 10,   // Timeout süresi (saniye)
    retry: 3       // Tekrar deneme sayısı
);
```

---

## Lisans

MIT License. Detaylar için [LICENSE](LICENSE) dosyasını inceleyin.

---

**Veribenim tarafından geliştirildi**

Web: [https://veribenim.com](https://veribenim.com)
Email: [support@veribenim.com](mailto:support@veribenim.com)
