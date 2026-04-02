# Veribenim PHP SDK

**KVKK & GDPR Compliant Data Protection and Cookie Management SDK**

[![Packagist Version](https://img.shields.io/packagist/v/veribenim/php-sdk.svg?style=flat-square)](https://packagist.org/packages/veribenim/php-sdk)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-blue?style=flat-square)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)
[![Downloads](https://img.shields.io/packagist/dm/veribenim/php-sdk.svg?style=flat-square)](https://packagist.org/packages/veribenim/php-sdk)

Veribenim PHP SDK is **the most comprehensive KVKK and GDPR compliant data security solution for all PHP applications**. Framework-agnostic, pure PHP integration for complete control over personal data processing — from Laravel to WordPress to vanilla PHP.

**Enterprise-grade Consent Management Platform (CMP) and Data Subject Rights (DSR) management based on Privacy by Design principles, works across the entire PHP ecosystem.**

---

## Table of Contents

- [Why Veribenim?](#why-veribenim)
- [Quick Start](#quick-start)
- [How to Install](#how-to-install)
- [Usage](#usage)
- [Security Standards](#security-standards)
- [Requirements](#requirements)
- [License](#license)

---

## Why Veribenim?

### ✅ Framework-Independent KVKK Solution

Works everywhere PHP runs:

- **Vanilla PHP**: Direct integration in raw PHP applications
- **Laravel**: With ServiceProvider and Facade
- **WordPress**: Plugin or SDK integration
- **Symfony**: Standalone SDK usage
- **Static sites**: SSG + API combinations
- **Legacy apps**: Compatible with PHP 8.1+

### 🔒 Enterprise Security Standards

- **End-to-End Encryption**: AES-256 (at rest), TLS 1.3+ (in transit)
- **Zero-Knowledge Architecture**: Veribenim servers cannot store personal data
- **GDPR Article 32**: Appropriate security measures (encryption, hashing, IP anonymization)
- **Audit Logs**: All operations securely recorded

### 📊 Complete DSAR Support

**All Data Subject Rights under GDPR Articles 15-22:**

- Right of Access
- Right to be Forgotten
- Right to Rectification
- Right to Restrict Processing
- Data Portability
- Right to Object
- Right Against Automated Decision-Making

### ⚡ Minimal Dependencies

- **Only 3 files**: VeribenimClient, Request, Response
- **Only ext-json and ext-curl**: No heavy frameworks needed
- **Fallback mechanism**: Uses file_get_contents if curl unavailable
- **~50KB**: Lightweight and fast

### 🚀 High Performance

- **Synchronous API**: Non-blocking, concurrent calls
- **Request caching**: No duplicate queries
- **Batch operations**: Send multiple operations in single request
- **Low memory footprint**: Minimal server resource usage

---

## Quick Start

```bash
composer require veribenim/php-sdk
```

```php
<?php
use Veribenim\VeribenimClient;

// Initialize SDK
$client = new VeribenimClient(
    token: 'your_api_token',
    lang: 'en',
    debug: false
);

// Log form consent
$client->logFormConsent(
    formName: 'contact',
    consented: true,
    consentText: 'I accept privacy terms',
    metadata: ['email' => 'user@example.com']
);

// Submit data subject request
$client->submitDsar(
    requestType: 'access',
    fullName: 'John Doe',
    email: 'john@example.com'
);
```

---

## How to Install

### Prerequisites

- **PHP** 8.1+
- **ext-curl** or **ext-json** (standard)
- **Composer**
- Veribenim account

### Installation Steps

**1. Install via Composer**

```bash
composer require veribenim/php-sdk
```

**2. Initialize VeribenimClient**

```php
<?php
require_once 'vendor/autoload.php';

use Veribenim\VeribenimClient;

$client = new VeribenimClient(
    token: 'your_site_token_here',
    lang: 'en',
    debug: false
);
```

**3. Environment variables (optional)**

```php
$client = new VeribenimClient(
    token: $_ENV['VERIBENIM_TOKEN'],
    lang: $_ENV['VERIBENIM_LANG'] ?? 'en',
    debug: $_ENV['APP_DEBUG'] ?? false
);
```

### Framework Integrations

**Laravel (ServiceProvider)**

```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->singleton(VeribenimClient::class, function () {
        return new VeribenimClient(
            token: config('veribenim.token'),
            lang: 'en',
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
            $lang: 'en'
            $debug: '%kernel.debug%'
```

**WordPress (Plugin)**

```php
$veribenim = new VeribenimClient(
    token: get_option('veribenim_token'),
    lang: 'en'
);

add_action('wp_footer', function() use ($veribenim) {
    $veribenim->logImpression();
});
```

---

## Usage

### VeribenimClient Class

Main class for all operations:

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

### Cookie Consent Tracking

#### logFormConsent — Form Submission

```php
$client->logFormConsent(
    formName: 'newsletter_signup',
    consented: true,
    consentText: 'I consent to newsletter subscription',
    metadata: [
        'email' => 'user@example.com',
        'source' => 'homepage'
    ]
);
```

**Parameters:**

| Parameter | Type | Description |
|---|---|---|
| `formName` | string | Form identifier |
| `consented` | bool | Consent status |
| `consentText` | string | Displayed text |
| `metadata` | array | Optional additional data |

#### getPreferences — Get User Preferences

```php
$sessionId = $_COOKIE['veribenim_session'] ?? null;

$preferences = $client->getPreferences($sessionId);

if ($preferences['analytics']) {
    echo "<script>gtag('config', 'GA_ID');</script>";
}
```

#### savePreferences — Save Preferences

```php
$client->savePreferences([
    'necessary' => true,
    'analytics' => true,
    'marketing' => false,
    'preferences' => true
]);
```

#### logImpression — Page Visit

```php
$client->logImpression();

// Or with analytics check
if ($client->getPreferences()['analytics']) {
    $client->logImpression();
}
```

#### logConsent — Manual Consent Recording

```php
$client->logConsent([
    'categories' => ['analytics', 'marketing'],
    'language' => 'en',
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'consent_timestamp' => date('c')
]);
```

### DSAR Operations

#### submitDsar — Data Subject Rights

```php
$response = $client->submitDsar(
    requestType: 'erasure',
    fullName: 'John Doe',
    email: 'john@example.com',
    description: 'Delete all my data'
);
```

**Supported DSAR Types:**

| Type | GDPR Article | Description |
|---|---|---|
| `access` | Article 15 | Right of access |
| `erasure` | Article 17 | Right to be forgotten |
| `rectification` | Article 16 | Right to correction |
| `restriction` | Article 18 | Right to restrict |
| `portability` | Article 20 | Data portability |
| `objection` | Article 21 | Right to object |
| `automated` | Article 22 | Right against automated decisions |

### Cookie Banner Integration

**Option 1: Manual HTML banner**

```html
<div id="cookie-banner">
    <p>We use cookies for essential services.</p>
    <button onclick="saveCookiePreferences()">Accept</button>
</div>

<script>
function saveCookiePreferences() {
    fetch('/api/save-preferences', {
        method: 'POST',
        body: JSON.stringify({
            necessary: true,
            analytics: true,
            marketing: false,
            preferences: true
        })
    });
}
</script>
```

**Option 2: Veribenim Bundle (recommended)**

```html
<script src="https://bundles.veribenim.com/your-site-token.js"></script>
```

---

## Security Standards

### GDPR Compliance

- **Article 6** — Lawful basis (Consent)
- **Article 7** — Conditions for consent
- **Articles 12-22** — Data subject rights
- **Article 25** — Privacy by Design
- **Article 32** — Security measures
- **EDPB Decisions** — Cookies and tracking

### Data Encryption

| Component | Technology | Standard |
|---|---|---|
| Database | AES-256-GCM | FIPS 140-2 |
| Transmission | TLS 1.3+ | RFC 8446 |
| Keys | ECDH P-256 | NIST SP 800-56A |
| Signing | HMAC-SHA256 | RFC 2104 |

### IP Anonymization

```
Real IP:    192.168.1.42
Anonymized: 192.168.0.0

IPv6:       2001:db8::1
Anonymized: 2001:db8::0
```

### Audit Logs

- Consent decisions
- Form submissions
- DSAR requests
- Data deletion
- API calls

Retained minimum **3 years**.

### Security Certifications

- ✓ ISO 27001
- ✓ ISO 27018
- ✓ SOC 2 Type II
- ✓ GDPR Compliant
- ✓ Annual Penetration Testing

---

## Requirements

### Software Requirements

```
PHP >= 8.1
ext-json (standard)
ext-curl (recommended) or ext-stream (fallback)
Composer
```

### Supported Platforms

| Platform | Version | Notes |
|---|---|---|
| PHP Vanilla | 8.1+ | Full support |
| Laravel | 10/11/12 | With ServiceProvider |
| Symfony | 6.x+ | Dependency injection |
| WordPress | 6.0+ | Plugin or SDK |
| Static PHP | Any | Direct integration |

### Production Configuration

```php
$client = new VeribenimClient(
    token: getenv('VERIBENIM_TOKEN'),
    lang: 'en',
    debug: false,
    timeout: 10,
    retry: 3
);
```

---

## License

MIT License

---

**Built with passion by Veribenim**

Web: [https://veribenim.com](https://veribenim.com)
Email: [support@veribenim.com](mailto:support@veribenim.com)
