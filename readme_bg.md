# Veribenim PHP SDK

**GDPR и KVKK Съответна SDK за Управление на Бисквитки и Защита на Данните**

[![Packagist Version](https://img.shields.io/packagist/v/veribenim/php-sdk.svg?style=flat-square)](https://packagist.org/packages/veribenim/php-sdk)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-blue?style=flat-square)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)
[![Downloads](https://img.shields.io/packagist/dm/veribenim/php-sdk.svg?style=flat-square)](https://packagist.org/packages/veribenim/php-sdk)

Veribenim PHP SDK е **най-комплексното решение за защита на данните и управление на бисквитки за всички PHP приложения**. Независимо от фреймворка, чистой PHP интеграция за пълен контрол над обработката на личните данни — от Laravel до WordPress до чист PHP.

**Платформа за управление на съгласието (CMP) и управление на правата на субектите на данни (DSAR) на ниво предприятие, базирана на принципите Privacy by Design, функционира в цялото PHP екосистема.**

---

## Съдържание

- [Защо Veribenim?](#защо-veribenim)
- [Бърз старт](#бърз-старт)
- [Инсталация](#инсталация)
- [Употреба](#употреба)
- [Стандарти за сигурност](#стандарти-за-сигурност)
- [Изисквания](#изисквания)
- [Лиценз](#лиценз)

---

## Защо Veribenim?

### ✅ Независимо от Фреймворка KVKK решение

Работи където угодно се изпълнява PHP:

- **Чист PHP**: Директна интеграция в чистей PHP приложения
- **Laravel**: С ServiceProvider и Facade
- **WordPress**: Интеграция на плъгин или SDK
- **Symfony**: Използване на автономната SDK
- **Статични уебсайтове**: Комбинации SSG + API
- **Наследени приложения**: Съвместимо с PHP 8.1+

### 🔒 Стандарти за сигурност на предприятието

- **Криптиране от край до край**: AES-256 (в покой), TLS 1.3+ (при предаване)
- **Архитектура без нулеви познания**: Сървърите на Veribenim не могат да съхранят данни
- **GDPR член 32**: Надлежни мерки за сигурност
- **Следи за одит**: Всички операции са сигурно записани

### 📊 Пълна DSAR поддръжка

**Всички права на субектите под GDPR членове 15-22:**

- Право на достъп
- Право да бъдат забравени
- Право на коригиране
- Право на ограничаване на обработката
- Преносимост на данни
- Право на възражение
- Право против автоматизирани решения

### ⚡ Минимални зависимости

- **Само 3 файла**: VeribenimClient, Request, Response
- **Само ext-json и ext-curl**: Не се нужни тежки фреймворки
- **Fallback механизъм**: Използва file_get_contents ако curl не е достъпен
- **~50KB**: Лек и бърз

### 🚀 Висока производителност

- **Синхронен API**: Неблокиращи повиквания
- **Кеш на заявки**: Без дублирани запитания
- **Партидни операции**: Изпращане на множество операции в една заявка
- **Нисък отпечатък памет**: Минимални ресурси на сървъра

---

## Бърз старт

```bash
composer require veribenim/php-sdk
```

```php
<?php
use Veribenim\VeribenimClient;

$client = new VeribenimClient(
    token: 'your_api_token',
    lang: 'bg',
    debug: false
);

$client->logFormConsent(
    formName: 'contact',
    consented: true,
    consentText: 'Съгласявам се с условията за защита на данните',
    metadata: ['email' => 'user@example.com']
);

$client->submitDsar(
    requestType: 'access',
    fullName: 'John Doe',
    email: 'john@example.com'
);
```

---

## Инсталация

### Предварителни условия

- **PHP** 8.1+
- **ext-curl** или **ext-json** (Standard)
- **Composer**
- Акаунт на Veribenim

### Стъпки на инсталация

**1. Инсталирайте чрез Composer**

```bash
composer require veribenim/php-sdk
```

**2. Инициализирайте VeribenimClient**

```php
<?php
require_once 'vendor/autoload.php';

use Veribenim\VeribenimClient;

$client = new VeribenimClient(
    token: 'your_site_token_here',
    lang: 'bg',
    debug: false
);
```

**3. Променливи на среда (по желание)**

```php
$client = new VeribenimClient(
    token: $_ENV['VERIBENIM_TOKEN'],
    lang: $_ENV['VERIBENIM_LANG'] ?? 'bg',
    debug: $_ENV['APP_DEBUG'] ?? false
);
```

### Интеграции на фреймворка

**Laravel (ServiceProvider)**

```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->singleton(VeribenimClient::class, function () {
        return new VeribenimClient(
            token: config('veribenim.token'),
            lang: 'bg',
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
            $lang: 'bg'
            $debug: '%kernel.debug%'
```

**WordPress (Plugin)**

```php
$veribenim = new VeribenimClient(
    token: get_option('veribenim_token'),
    lang: 'bg'
);

add_action('wp_footer', function() use ($veribenim) {
    $veribenim->logImpression();
});
```

---

## Употреба

### Клас VeribenimClient

Основен клас за всички операции:

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

### Проследяване на съгласието за бисквитки

#### logFormConsent — Изпращане на формуляр

```php
$client->logFormConsent(
    formName: 'newsletter_signup',
    consented: true,
    consentText: 'Согласявам се на обработката на данните си',
    metadata: ['email' => 'user@example.com']
);
```

**Параметри:**

| Параметър | Тип | Описание |
|---|---|---|
| `formName` | string | Идентификатор на формуляра |
| `consented` | bool | Статус на съгласието |
| `consentText` | string | Показан текст |
| `metadata` | array | Опционални допълнителни данни |

#### getPreferences — Получаване на предпочитания

```php
$preferences = $client->getPreferences();

if ($preferences['analytics']) {
    echo "<script>gtag('config', 'GA_ID');</script>";
}
```

#### savePreferences — Запазване на предпочитанията

```php
$client->savePreferences([
    'necessary' => true,
    'analytics' => true,
    'marketing' => false,
    'preferences' => true
]);
```

#### logImpression — Посещение на страница

```php
$client->logImpression();
```

#### logConsent — Ръчно записване на съгласието

```php
$client->logConsent([
    'categories' => ['analytics', 'marketing'],
    'language' => 'bg',
    'consent_timestamp' => date('c')
]);
```

### DSAR операции

#### submitDsar — Права на субектите

```php
$response = $client->submitDsar(
    requestType: 'erasure',
    fullName: 'John Doe',
    email: 'john@example.com',
    description: 'Изтрийте всичките ми данни'
);
```

**Поддържани DSAR типове:**

| Тип | GDPR член | Описание |
|---|---|---|
| `access` | Член 15 | Право на достъп |
| `erasure` | Член 17 | Право да бъдат забравени |
| `rectification` | Член 16 | Право на коригиране |
| `restriction` | Член 18 | Право на ограничаване |
| `portability` | Член 20 | Преносимост на данни |
| `objection` | Член 21 | Право на възражение |
| `automated` | Член 22 | Права против автоматизирани решения |

### Интеграция на банер за бисквитки

**Опция 1: Ръчен HTML банер**

```html
<div id="cookie-banner">
    <p>Използваме бисквитки за основните услуги.</p>
    <button onclick="saveCookiePreferences()">Приеми</button>
</div>
```

**Опция 2: Veribenim Bundle (препоръчана)**

```html
<script src="https://bundles.veribenim.com/your-site-token.js"></script>
```

---

## Стандарти за сигурност

### GDPR съответствие

- **Член 6** — Законен основание
- **Член 7** — Условия за съгласие
- **Член 25** — Защита на приватността чрез дизайн
- **Член 32** — Мерки за сигурност
- **Указания на EDPB** — Бисквитки и проследяване

### Криптиране на данни

| Компонент | Технология | Норма |
|---|---|---|
| База данни | AES-256-GCM | FIPS 140-2 |
| Предаване | TLS 1.3+ | RFC 8446 |
| Ключове | ECDH P-256 | NIST SP 800-56A |
| Подпис | HMAC-SHA256 | RFC 2104 |

### Анонимизиране на IP

```
Истински IP:    192.168.1.42
Анонимизиран:   192.168.0.0

IPv6:           2001:db8::1
Анонимизиран:   2001:db8::0
```

### Следи за одит

- Решения за съгласие
- Изпращания на формуляри
- DSAR искания
- Изтриване на данни
- API повиквания

Съхранявани минимум **3 години**.

### Сертификати за сигурност

- ✓ ISO 27001
- ✓ ISO 27018
- ✓ SOC 2 Type II
- ✓ GDPR съответно
- ✓ Годишни тестове за пенетрация

---

## Изисквания

### Софтуерни изисквания

```
PHP >= 8.1
ext-json (standard)
ext-curl (препоръчана) или ext-stream (fallback)
Composer
```

### Поддържани платформи

| Платформа | Версия | Забележки |
|---|---|---|
| Чист PHP | 8.1+ | Пълна поддръжка |
| Laravel | 10/11/12 | С ServiceProvider |
| Symfony | 6.x+ | Injection of dependency |
| WordPress | 6.0+ | Плъгин или SDK |
| Статичен PHP | Any | Директна интеграция |

### Production конфигурация

```php
$client = new VeribenimClient(
    token: getenv('VERIBENIM_TOKEN'),
    lang: 'bg',
    debug: false,
    timeout: 10,
    retry: 3
);
```

---

## Лиценз

MIT License

---

**Разработено с любов от Veribenim**

Web: [https://veribenim.com](https://veribenim.com)
Email: [support@veribenim.com](mailto:support@veribenim.com)
