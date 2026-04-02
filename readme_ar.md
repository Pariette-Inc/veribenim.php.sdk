# Veribenim PHP SDK

**SDK لإدارة ملفات تعريف الارتباط والخصوصية المتوافقة مع GDPR و KVKK**

<div dir="rtl">

[![Packagist Version](https://img.shields.io/packagist/v/veribenim/php-sdk.svg?style=flat-square)](https://packagist.org/packages/veribenim/php-sdk)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-blue?style=flat-square)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)
[![Downloads](https://img.shields.io/packagist/dm/veribenim/php-sdk.svg?style=flat-square)](https://packagist.org/packages/veribenim/php-sdk)

Veribenim PHP SDK هو **الحل الأكثر شمولاً لحماية البيانات وإدارة ملفات تعريف الارتباط لجميع تطبيقات PHP**. مستقل عن الإطار، تكامل PHP نقي للتحكم الكامل في معالجة البيانات الشخصية — من Laravel إلى WordPress إلى PHP النقي.

**منصة إدارة الموافقة (CMP) وإدارة حقوق موضوع البيانات (DSAR) على مستوى المؤسسات بناءً على مبادئ الخصوصية بالتصميم، تعمل في جميع أنحاء نظام PHP البيئي.**

---

## جدول المحتويات

- [لماذا Veribenim؟](#لماذا-veribenim)
- [البدء السريع](#البدء-السريع)
- [التثبيت](#التثبيت)
- [الاستخدام](#الاستخدام)
- [معايير الأمان](#معايير-الأمان)
- [المتطلبات](#المتطلبات)
- [الترخيص](#الترخيص)

---

## لماذا Veribenim؟

### ✅ حل KVKK مستقل عن الإطار

يعمل في كل مكان يعمل فيه PHP:

- **PHP النقي**: التكامل المباشر في تطبيقات PHP النقية
- **Laravel**: مع ServiceProvider و Facade
- **WordPress**: تكامل البرنامج المساعد أو SDK
- **Symfony**: استخدام SDK المستقل
- **المواقع الثابتة**: تجميعات SSG + API
- **التطبيقات القديمة**: متوافق مع PHP 8.1+

### 🔒 معايير أمان المؤسسة

- **التشفير من طرف إلى طرف**: AES-256 (في الراحة) و TLS 1.3+ (في الطريق)
- **هندسة معمارية بدون معرفة**: لا يمكن لخوادم Veribenim تخزين البيانات
- **مادة GDPR 32**: التدابير الأمنية المناسبة
- **سجلات التدقيق**: تم تسجيل جميع العمليات بشكل آمن

### 📊 دعم DSAR الكامل

**جميع حقوق الموضوع بموجب مواد GDPR 15-22:**

- حق الوصول
- الحق في النسيان
- حق التصحيح
- حق تقييد المعالجة
- قابلية نقل البيانات
- حق الاعتراض
- الحق ضد القرارات الآلية

### ⚡ الحد الأدنى من التبعيات

- **ملفات 3 فقط**: VeribenimClient و Request و Response
- **فقط ext-json و ext-curl**: لا تحتاج أطر ثقيلة
- **آلية الرجوع**: تستخدم file_get_contents إذا لم يكن curl متاحاً
- **~50KB**: خفيف وسريع

### 🚀 أداء عالي

- **واجهة برمجية متزامنة**: استدعاءات غير حجزية
- **تخزين مؤقت للطلبات**: بدون استعلامات مكررة
- **العمليات الدفعية**: إرسال عمليات متعددة في طلب واحد
- **بصمة ذاكرة منخفضة**: الحد الأدنى من موارد الخادم

---

## البدء السريع

```bash
composer require veribenim/php-sdk
```

```php
<?php
use Veribenim\VeribenimClient;

$client = new VeribenimClient(
    token: 'your_api_token',
    lang: 'ar',
    debug: false
);

$client->logFormConsent(
    formName: 'contact',
    consented: true,
    consentText: 'أوافق على معالجة البيانات',
    metadata: ['email' => 'user@example.com']
);

$client->submitDsar(
    requestType: 'access',
    fullName: 'John Doe',
    email: 'john@example.com'
);
```

---

## التثبيت

### المتطلبات الأساسية

- **PHP** 8.1+
- **ext-curl** أو **ext-json** (قياسي)
- **Composer**
- حساب Veribenim

### خطوات التثبيت

**1. التثبيت عبر Composer**

```bash
composer require veribenim/php-sdk
```

**2. تهيئة VeribenimClient**

```php
<?php
require_once 'vendor/autoload.php';

use Veribenim\VeribenimClient;

$client = new VeribenimClient(
    token: 'your_site_token_here',
    lang: 'ar',
    debug: false
);
```

**3. متغيرات البيئة (اختياري)**

```php
$client = new VeribenimClient(
    token: $_ENV['VERIBENIM_TOKEN'],
    lang: $_ENV['VERIBENIM_LANG'] ?? 'ar',
    debug: $_ENV['APP_DEBUG'] ?? false
);
```

### تكاملات الإطار

**Laravel (ServiceProvider)**

```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->singleton(VeribenimClient::class, function () {
        return new VeribenimClient(
            token: config('veribenim.token'),
            lang: 'ar',
            debug: config('app.debug')
        );
    });
}
```

**Symfony (Service)**

```yaml
# config/services.yaml
services:
    Veribenim\VeribenimClient:
        arguments:
            $token: '%env(VERIBENIM_TOKEN)%'
            $lang: 'ar'
            $debug: '%kernel.debug%'
```

**WordPress (Plugin)**

```php
$veribenim = new VeribenimClient(
    token: get_option('veribenim_token'),
    lang: 'ar'
);

add_action('wp_footer', function() use ($veribenim) {
    $veribenim->logImpression();
});
```

---

## الاستخدام

### فئة VeribenimClient

الفئة الرئيسية لجميع العمليات:

```php
use Veribenim\VeribenimClient;

$client = new VeribenimClient('TOKEN_HERE');

$client->logFormConsent(...);
$client->getPreferences(...);
$client->savePreferences(...);
$client->logImpression();
$client->logConsent(...);
$client->submitDsar(...);
```

### تتبع موافقة ملفات تعريف الارتباط

#### logFormConsent — تقديم النموذج

```php
$client->logFormConsent(
    formName: 'newsletter_signup',
    consented: true,
    consentText: 'أوافق على معالجة بياناتي',
    metadata: ['email' => 'user@example.com']
);
```

**المعاملات:**

| المعامل | النوع | الوصف |
|---|---|---|
| `formName` | string | معرف النموذج |
| `consented` | bool | حالة الموافقة |
| `consentText` | string | النص المعروض |
| `metadata` | array | بيانات إضافية اختيارية |

#### getPreferences — الحصول على التفضيلات

```php
$preferences = $client->getPreferences();

if ($preferences['analytics']) {
    echo "<script>gtag('config', 'GA_ID');</script>";
}
```

#### savePreferences — حفظ التفضيلات

```php
$client->savePreferences([
    'necessary' => true,
    'analytics' => true,
    'marketing' => false,
    'preferences' => true
]);
```

#### logImpression — زيارة الصفحة

```php
$client->logImpression();
```

#### logConsent — تسجيل الموافقة اليدوية

```php
$client->logConsent([
    'categories' => ['analytics', 'marketing'],
    'language' => 'ar',
    'consent_timestamp' => date('c')
]);
```

### عمليات DSAR

#### submitDsar — حقوق الموضوع

```php
$response = $client->submitDsar(
    requestType: 'erasure',
    fullName: 'John Doe',
    email: 'john@example.com',
    description: 'حذف كل بياناتي'
);
```

**أنواع DSAR المدعومة:**

| النوع | مادة GDPR | الوصف |
|---|---|---|
| `access` | المادة 15 | حق الوصول |
| `erasure` | المادة 17 | الحق في النسيان |
| `rectification` | المادة 16 | حق التصحيح |
| `restriction` | المادة 18 | حق التقييد |
| `portability` | المادة 20 | قابلية نقل البيانات |
| `objection` | المادة 21 | حق الاعتراض |
| `automated` | المادة 22 | الحقوق ضد القرارات الآلية |

### تكامل لافتة ملفات تعريف الارتباط

**الخيار 1: لافتة HTML يدوية**

```html
<div id="cookie-banner">
    <p>نستخدم ملفات تعريف الارتباط للخدمات الأساسية.</p>
    <button onclick="saveCookiePreferences()">قبول</button>
</div>
```

**الخيار 2: حزمة Veribenim (موصى به)**

```html
<script src="https://bundles.veribenim.com/your-site-token.js"></script>
```

---

## معايير الأمان

### امتثال GDPR

- **المادة 6** — الأساس القانوني
- **المادة 7** — شروط الموافقة
- **المادة 25** — الخصوصية بالتصميم
- **المادة 32** — تدابير الأمان
- **إرشادات EDPB** — ملفات تعريف الارتباط والتتبع

### تشفير البيانات

| المكون | التكنولوجيا | المعيار |
|---|---|---|
| قاعدة البيانات | AES-256-GCM | FIPS 140-2 |
| النقل | TLS 1.3+ | RFC 8446 |
| المفاتيح | ECDH P-256 | NIST SP 800-56A |
| التوقيع | HMAC-SHA256 | RFC 2104 |

### إخفاء الهوية عن IP

```
IP الفعلي:      192.168.1.42
مخفي الهوية:    192.168.0.0

IPv6:           2001:db8::1
مخفي الهوية:    2001:db8::0
```

### سجلات التدقيق

- قرارات الموافقة
- تقديمات النموذج
- طلبات DSAR
- حذف البيانات
- استدعاءات API

الاحتفاظ بها الحد الأدنى **3 سنوات**.

### شهادات الأمان

- ✓ ISO 27001
- ✓ ISO 27018
- ✓ SOC 2 Type II
- ✓ متوافق مع GDPR
- ✓ اختبارات الاختراق السنوية

---

## المتطلبات

### متطلبات البرنامج

```
PHP >= 8.1
ext-json (قياسي)
ext-curl (موصى به) أو ext-stream (احتياطي)
Composer
```

### المنصات المدعومة

| المنصة | الإصدار | الملاحظات |
|---|---|---|
| PHP النقي | 8.1+ | دعم كامل |
| Laravel | 10/11/12 | مع ServiceProvider |
| Symfony | 6.x+ | Dependency injection |
| WordPress | 6.0+ | البرنامج المساعد أو SDK |
| PHP الثابت | أي | التكامل المباشر |

### تكوين الإنتاج

```php
$client = new VeribenimClient(
    token: getenv('VERIBENIM_TOKEN'),
    lang: 'ar',
    debug: false,
    timeout: 10,
    retry: 3
);
```

---

## الترخيص

MIT License

---

**تم التطوير بحب من قبل Veribenim**

Web: [https://veribenim.com](https://veribenim.com)
Email: [support@veribenim.com](mailto:support@veribenim.com)

</div>
