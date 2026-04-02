# Veribenim PHP SDK

**DSGVO & KVKK-konforme Datenschutz- und Cookie-Management-SDK**

[![Packagist Version](https://img.shields.io/packagist/v/veribenim/php-sdk.svg?style=flat-square)](https://packagist.org/packages/veribenim/php-sdk)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-blue?style=flat-square)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)
[![Downloads](https://img.shields.io/packagist/dm/veribenim/php-sdk.svg?style=flat-square)](https://packagist.org/packages/veribenim/php-sdk)

Veribenim PHP SDK ist **die umfassendste DSGVO und KVKK-konforme Datenschutzlösung für alle PHP-Anwendungen**. Framework-unabhängig, reine PHP-Integration für vollständige Kontrolle über die Datenverarbeitung — von Laravel bis WordPress bis reines PHP.

**Enterprise-Grade Consent Management Platform (CMP) und Data Subject Rights (DSR) Management basierend auf Privacy-by-Design-Prinzipien, funktioniert im gesamten PHP-Ökosystem.**

---

## Inhaltsverzeichnis

- [Warum Veribenim?](#warum-veribenim)
- [Schnellstart](#schnellstart)
- [Installation](#installation)
- [Verwendung](#verwendung)
- [Sicherheitsstandards](#sicherheitsstandards)
- [Anforderungen](#anforderungen)
- [Lizenz](#lizenz)

---

## Warum Veribenim?

### ✅ Framework-unabhängige KVKK-Lösung

Funktioniert überall, wo PHP läuft:

- **Vanilla PHP**: Direkte Integration in reinen PHP-Anwendungen
- **Laravel**: Mit ServiceProvider und Facade
- **WordPress**: Plugin oder SDK-Integration
- **Symfony**: Standalone-SDK-Nutzung
- **Statische Websites**: SSG + API-Kombinationen
- **Legacy-Apps**: Kompatibel mit PHP 8.1+

### 🔒 Enterprise-Sicherheitsstandards

- **End-to-End-Verschlüsselung**: AES-256 (in Ruhe), TLS 1.3+ (im Transit)
- **Zero-Knowledge-Architektur**: Veribenim-Server können keine Daten speichern
- **DSGVO Artikel 32**: Angemessene Sicherheitsmaßnahmen
- **Audit-Trails**: Alle Operationen sicher protokolliert

### 📊 Vollständige DSAR-Unterstützung

**Alle Betroffenenrechte nach DSGVO Artikel 15-22:**

- Auskunftsrecht
- Recht auf Vergessenwerden
- Berichtigungsrecht
- Einschränkungsrecht
- Datenportabilität
- Widerspruchsrecht
- Recht gegen automatisierte Entscheidungen

### ⚡ Minimale Abhängigkeiten

- **Nur 3 Dateien**: VeribenimClient, Request, Response
- **Nur ext-json und ext-curl**: Keine schweren Frameworks nötig
- **Fallback-Mechanismus**: Verwendet file_get_contents wenn curl nicht verfügbar
- **~50KB**: Leicht und schnell

### 🚀 Hohe Leistung

- **Synchrone API**: Nicht-blockierend
- **Request-Caching**: Keine doppelten Abfragen
- **Batch-Operationen**: Mehrere Operationen in einer Anfrage
- **Niedriger Speicherfußabdruck**: Minimale Serverressourcen

---

## Schnellstart

```bash
composer require veribenim/php-sdk
```

```php
<?php
use Veribenim\VeribenimClient;

$client = new VeribenimClient(
    token: 'your_api_token',
    lang: 'de',
    debug: false
);

$client->logFormConsent(
    formName: 'contact',
    consented: true,
    consentText: 'Ich akzeptiere Datenschutzbestimmungen',
    metadata: ['email' => 'user@example.com']
);

$client->submitDsar(
    requestType: 'access',
    fullName: 'John Doe',
    email: 'john@example.com'
);
```

---

## Installation

### Voraussetzungen

- **PHP** 8.1+
- **ext-curl** oder **ext-json** (Standard)
- **Composer**
- Veribenim-Konto

### Installationsschritte

**1. Installation via Composer**

```bash
composer require veribenim/php-sdk
```

**2. VeribenimClient initialisieren**

```php
<?php
require_once 'vendor/autoload.php';

use Veribenim\VeribenimClient;

$client = new VeribenimClient(
    token: 'your_site_token_here',
    lang: 'de',
    debug: false
);
```

**3. Umgebungsvariablen (optional)**

```php
$client = new VeribenimClient(
    token: $_ENV['VERIBENIM_TOKEN'],
    lang: $_ENV['VERIBENIM_LANG'] ?? 'de',
    debug: $_ENV['APP_DEBUG'] ?? false
);
```

### Framework-Integrationen

**Laravel (ServiceProvider)**

```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->singleton(VeribenimClient::class, function () {
        return new VeribenimClient(
            token: config('veribenim.token'),
            lang: 'de',
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
            $lang: 'de'
            $debug: '%kernel.debug%'
```

**WordPress (Plugin)**

```php
$veribenim = new VeribenimClient(
    token: get_option('veribenim_token'),
    lang: 'de'
);

add_action('wp_footer', function() use ($veribenim) {
    $veribenim->logImpression();
});
```

---

## Verwendung

### VeribenimClient-Klasse

Hauptklasse für alle Operationen:

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

### Cookie-Zustimmungsverfolgung

#### logFormConsent — Formulareinreichung

```php
$client->logFormConsent(
    formName: 'newsletter_signup',
    consented: true,
    consentText: 'Ich stimme der Verarbeitung zu',
    metadata: ['email' => 'user@example.com']
);
```

**Parameter:**

| Parameter | Typ | Beschreibung |
|---|---|---|
| `formName` | string | Formular-ID |
| `consented` | bool | Zustimmungsstatus |
| `consentText` | string | Angezeigter Text |
| `metadata` | array | Optionale Zusatzdaten |

#### getPreferences — Einstellungen abrufen

```php
$preferences = $client->getPreferences();

if ($preferences['analytics']) {
    echo "<script>gtag('config', 'GA_ID');</script>";
}
```

#### savePreferences — Einstellungen speichern

```php
$client->savePreferences([
    'necessary' => true,
    'analytics' => true,
    'marketing' => false,
    'preferences' => true
]);
```

#### logImpression — Seitenzugriff

```php
$client->logImpression();
```

#### logConsent — Manuelle Zustimmung

```php
$client->logConsent([
    'categories' => ['analytics', 'marketing'],
    'language' => 'de',
    'consent_timestamp' => date('c')
]);
```

### DSAR-Operationen

#### submitDsar — Betroffenenrechte

```php
$response = $client->submitDsar(
    requestType: 'erasure',
    fullName: 'John Doe',
    email: 'john@example.com',
    description: 'Alle Daten löschen'
);
```

**Unterstützte DSAR-Typen:**

| Typ | DSGVO-Artikel | Beschreibung |
|---|---|---|
| `access` | Artikel 15 | Auskunftsrecht |
| `erasure` | Artikel 17 | Recht auf Vergessenwerden |
| `rectification` | Artikel 16 | Berichtigungsrecht |
| `restriction` | Artikel 18 | Einschränkungsrecht |
| `portability` | Artikel 20 | Datenportabilität |
| `objection` | Artikel 21 | Widerspruchsrecht |
| `automated` | Artikel 22 | Recht gegen automatisierte Entscheidungen |

### Cookie-Banner-Integration

**Option 1: Manuelles HTML-Banner**

```html
<div id="cookie-banner">
    <p>Wir verwenden Cookies für essenzielle Dienste.</p>
    <button onclick="saveCookiePreferences()">Akzeptieren</button>
</div>
```

**Option 2: Veribenim Bundle (empfohlen)**

```html
<script src="https://bundles.veribenim.com/your-site-token.js"></script>
```

---

## Sicherheitsstandards

### DSGVO-Konformität

- **Artikel 6** — Rechtmäßigkeit
- **Artikel 7** — Zustimmungsbedingungen
- **Artikel 25** — Privacy by Design
- **Artikel 32** — Sicherheitsmaßnahmen
- **EDSA-Richtlinien** — Cookies und Tracking

### Datenverschlüsselung

| Komponente | Technologie | Norm |
|---|---|---|
| Datenbank | AES-256-GCM | FIPS 140-2 |
| Übertragung | TLS 1.3+ | RFC 8446 |
| Schlüssel | ECDH P-256 | NIST SP 800-56A |
| Signatur | HMAC-SHA256 | RFC 2104 |

### IP-Anonymisierung

```
Echte IP:      192.168.1.42
Anonymisiert:  192.168.0.0

IPv6:          2001:db8::1
Anonymisiert:  2001:db8::0
```

### Audit-Logs

- Zustimmungsentscheidungen
- Formulareinreichungen
- DSAR-Anfragen
- Datenlöschung
- API-Aufrufe

Mindestens **3 Jahre** aufbewahrt.

### Sicherheitszertifikate

- ✓ ISO 27001
- ✓ ISO 27018
- ✓ SOC 2 Type II
- ✓ DSGVO-konform
- ✓ Jährliche Penetrationstests

---

## Anforderungen

### Softwareanforderungen

```
PHP >= 8.1
ext-json (Standard)
ext-curl (empfohlen) oder ext-stream (Fallback)
Composer
```

### Unterstützte Plattformen

| Plattform | Version | Hinweise |
|---|---|---|
| PHP Vanilla | 8.1+ | Vollständiger Support |
| Laravel | 10/11/12 | Mit ServiceProvider |
| Symfony | 6.x+ | Dependency Injection |
| WordPress | 6.0+ | Plugin oder SDK |
| Statisches PHP | Any | Direkte Integration |

### Production-Konfiguration

```php
$client = new VeribenimClient(
    token: getenv('VERIBENIM_TOKEN'),
    lang: 'de',
    debug: false,
    timeout: 10,
    retry: 3
);
```

---

## Lizenz

MIT License

---

**Entwickelt mit Liebe von Veribenim**

Web: [https://veribenim.com](https://veribenim.com)
Email: [support@veribenim.com](mailto:support@veribenim.com)
