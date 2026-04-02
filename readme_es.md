# Veribenim PHP SDK

**SDK de Gestión de Privacidad y Cookies Conforme con GDPR y KVKK**

[![Packagist Version](https://img.shields.io/packagist/v/veribenim/php-sdk.svg?style=flat-square)](https://packagist.org/packages/veribenim/php-sdk)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-blue?style=flat-square)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)
[![Downloads](https://img.shields.io/packagist/dm/veribenim/php-sdk.svg?style=flat-square)](https://packagist.org/packages/veribenim/php-sdk)

Veribenim PHP SDK es **la solución más completa de protección de datos y gestión de cookies para todas las aplicaciones PHP**. Independiente del framework, integración pura PHP para control total del procesamiento de datos personales — de Laravel a WordPress a PHP puro.

**Plataforma de Gestión de Consentimiento (CMP) y gestión de Derechos de Sujetos de Datos (DSAR) de nivel empresarial basada en principios Privacy by Design, funciona en todo el ecosistema PHP.**

---

## Tabla de contenidos

- [¿Por qué Veribenim?](#por-qué-veribenim)
- [Inicio rápido](#inicio-rápido)
- [Instalación](#instalación)
- [Uso](#uso)
- [Estándares de seguridad](#estándares-de-seguridad)
- [Requisitos](#requisitos)
- [Licencia](#licencia)

---

## ¿Por qué Veribenim?

### ✅ Solución KVKK Independiente del Framework

Funciona en todas partes donde PHP se ejecuta:

- **PHP puro**: Integración directa en aplicaciones PHP puras
- **Laravel**: Con ServiceProvider y Facade
- **WordPress**: Integración plugin o SDK
- **Symfony**: Uso del SDK autonomo
- **Sitios estáticos**: Combinaciones SSG + API
- **Aplicaciones heredadas**: Compatible con PHP 8.1+

### 🔒 Estándares de Seguridad Empresarial

- **Cifrado de extremo a extremo**: AES-256 (en reposo), TLS 1.3+ (en tránsito)
- **Arquitectura zero-knowledge**: Los servidores de Veribenim no pueden almacenar datos
- **Artículo 32 GDPR**: Medidas de seguridad apropiadas
- **Pistas de auditoría**: Todas las operaciones registradas de forma segura

### 📊 Soporte Completo DSAR

**Todos los Derechos de Sujetos bajo los Artículos 15-22 del GDPR:**

- Derecho de acceso
- Derecho al olvido
- Derecho a la rectificación
- Derecho a limitar el procesamiento
- Portabilidad de datos
- Derecho de objeción
- Derecho contra decisiones automatizadas

### ⚡ Dependencias Mínimas

- **Solo 3 archivos**: VeribenimClient, Request, Response
- **Solo ext-json y ext-curl**: No se necesitan frameworks pesados
- **Mecanismo de fallback**: Usa file_get_contents si curl no está disponible
- **~50KB**: Ligero y rápido

### 🚀 Alto Rendimiento

- **API sincrónica**: Llamadas no-bloqueantes
- **Caché de solicitudes**: Sin consultas duplicadas
- **Operaciones por lotes**: Envía múltiples operaciones en una sola solicitud
- **Huella de memoria baja**: Recursos de servidor mínimos

---

## Inicio rápido

```bash
composer require veribenim/php-sdk
```

```php
<?php
use Veribenim\VeribenimClient;

$client = new VeribenimClient(
    token: 'your_api_token',
    lang: 'es',
    debug: false
);

$client->logFormConsent(
    formName: 'contact',
    consented: true,
    consentText: 'Acepto los términos de privacidad',
    metadata: ['email' => 'user@example.com']
);

$client->submitDsar(
    requestType: 'access',
    fullName: 'John Doe',
    email: 'john@example.com'
);
```

---

## Instalación

### Requisitos previos

- **PHP** 8.1+
- **ext-curl** o **ext-json** (Standard)
- **Composer**
- Cuenta Veribenim

### Pasos de instalación

**1. Instalar vía Composer**

```bash
composer require veribenim/php-sdk
```

**2. Inicializar VeribenimClient**

```php
<?php
require_once 'vendor/autoload.php';

use Veribenim\VeribenimClient;

$client = new VeribenimClient(
    token: 'your_site_token_here',
    lang: 'es',
    debug: false
);
```

**3. Variables de entorno (opcional)**

```php
$client = new VeribenimClient(
    token: $_ENV['VERIBENIM_TOKEN'],
    lang: $_ENV['VERIBENIM_LANG'] ?? 'es',
    debug: $_ENV['APP_DEBUG'] ?? false
);
```

### Integraciones de Framework

**Laravel (ServiceProvider)**

```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->singleton(VeribenimClient::class, function () {
        return new VeribenimClient(
            token: config('veribenim.token'),
            lang: 'es',
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
            $lang: 'es'
            $debug: '%kernel.debug%'
```

**WordPress (Plugin)**

```php
$veribenim = new VeribenimClient(
    token: get_option('veribenim_token'),
    lang: 'es'
);

add_action('wp_footer', function() use ($veribenim) {
    $veribenim->logImpression();
});
```

---

## Uso

### Clase VeribenimClient

Clase principal para todas las operaciones:

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

### Seguimiento de Consentimiento de Cookies

#### logFormConsent — Envío de Formulario

```php
$client->logFormConsent(
    formName: 'newsletter_signup',
    consented: true,
    consentText: 'Doy mi consentimiento para el procesamiento de datos',
    metadata: ['email' => 'user@example.com']
);
```

**Parámetros:**

| Parámetro | Tipo | Descripción |
|---|---|---|
| `formName` | string | ID del formulario |
| `consented` | bool | Estado del consentimiento |
| `consentText` | string | Texto mostrado |
| `metadata` | array | Datos adicionales opcionales |

#### getPreferences — Obtener Preferencias

```php
$preferences = $client->getPreferences();

if ($preferences['analytics']) {
    echo "<script>gtag('config', 'GA_ID');</script>";
}
```

#### savePreferences — Guardar Preferencias

```php
$client->savePreferences([
    'necessary' => true,
    'analytics' => true,
    'marketing' => false,
    'preferences' => true
]);
```

#### logImpression — Visita de Página

```php
$client->logImpression();
```

#### logConsent — Registro Manual de Consentimiento

```php
$client->logConsent([
    'categories' => ['analytics', 'marketing'],
    'language' => 'es',
    'consent_timestamp' => date('c')
]);
```

### Operaciones DSAR

#### submitDsar — Derechos de Sujetos

```php
$response = $client->submitDsar(
    requestType: 'erasure',
    fullName: 'John Doe',
    email: 'john@example.com',
    description: 'Eliminar todos mis datos'
);
```

**Tipos DSAR Soportados:**

| Tipo | Artículo GDPR | Descripción |
|---|---|---|
| `access` | Artículo 15 | Derecho de acceso |
| `erasure` | Artículo 17 | Derecho al olvido |
| `rectification` | Artículo 16 | Derecho a la rectificación |
| `restriction` | Artículo 18 | Derecho a limitar |
| `portability` | Artículo 20 | Portabilidad de datos |
| `objection` | Artículo 21 | Derecho de objeción |
| `automated` | Artículo 22 | Derechos contra decisiones automatizadas |

### Integración de Banner de Cookies

**Opción 1: Banner HTML manual**

```html
<div id="cookie-banner">
    <p>Utilizamos cookies para servicios esenciales.</p>
    <button onclick="saveCookiePreferences()">Aceptar</button>
</div>
```

**Opción 2: Bundle de Veribenim (recomendado)**

```html
<script src="https://bundles.veribenim.com/your-site-token.js"></script>
```

---

## Estándares de seguridad

### Conformidad GDPR

- **Artículo 6** — Base legal
- **Artículo 7** — Condiciones del consentimiento
- **Artículo 25** — Privacidad por diseño
- **Artículo 32** — Medidas de seguridad
- **Directrices AEPD** — Cookies y seguimiento

### Cifrado de datos

| Componente | Tecnología | Norma |
|---|---|---|
| Base de datos | AES-256-GCM | FIPS 140-2 |
| Transmisión | TLS 1.3+ | RFC 8446 |
| Claves | ECDH P-256 | NIST SP 800-56A |
| Firma | HMAC-SHA256 | RFC 2104 |

### Anonimización de IP

```
IP real:        192.168.1.42
Anonimizada:    192.168.0.0

IPv6:           2001:db8::1
Anonimizada:    2001:db8::0
```

### Registros de auditoría

- Decisiones de consentimiento
- Envíos de formularios
- Solicitudes DSAR
- Eliminación de datos
- Llamadas API

Retenidas mínimo **3 años**.

### Certificados de seguridad

- ✓ ISO 27001
- ✓ ISO 27018
- ✓ SOC 2 Type II
- ✓ Conforme a GDPR
- ✓ Pruebas de penetración anuales

---

## Requisitos

### Requisitos de software

```
PHP >= 8.1
ext-json (estándar)
ext-curl (recomendado) o ext-stream (fallback)
Composer
```

### Plataformas soportadas

| Plataforma | Versión | Notas |
|---|---|---|
| PHP puro | 8.1+ | Soporte completo |
| Laravel | 10/11/12 | Con ServiceProvider |
| Symfony | 6.x+ | Inyección de dependencia |
| WordPress | 6.0+ | Plugin o SDK |
| PHP estático | Any | Integración directa |

### Configuración de producción

```php
$client = new VeribenimClient(
    token: getenv('VERIBENIM_TOKEN'),
    lang: 'es',
    debug: false,
    timeout: 10,
    retry: 3
);
```

---

## Licencia

MIT License

---

**Desarrollado con pasión por Veribenim**

Web: [https://veribenim.com](https://veribenim.com)
Email: [support@veribenim.com](mailto:support@veribenim.com)
