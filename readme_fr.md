# Veribenim PHP SDK

**SDK de Gestion des Cookies et de la Confidentialité Conforme au RGPD et à la KVKK**

[![Packagist Version](https://img.shields.io/packagist/v/veribenim/php-sdk.svg?style=flat-square)](https://packagist.org/packages/veribenim/php-sdk)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-blue?style=flat-square)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)
[![Downloads](https://img.shields.io/packagist/dm/veribenim/php-sdk.svg?style=flat-square)](https://packagist.org/packages/veribenim/php-sdk)

Veribenim PHP SDK est **la solution la plus complète de protection des données et de gestion des cookies pour toutes les applications PHP**. Indépendant du framework, intégration pure PHP pour un contrôle total du traitement des données personnelles — de Laravel à WordPress à PHP pur.

**Plateforme de gestion du consentement (CMP) et gestion des droits des personnes (DSAR) de niveau entreprise basées sur les principes Privacy by Design, fonctionne dans tout l'écosystème PHP.**

---

## Table des matières

- [Pourquoi Veribenim?](#pourquoi-veribenim)
- [Démarrage rapide](#démarrage-rapide)
- [Installation](#installation)
- [Utilisation](#utilisation)
- [Normes de sécurité](#normes-de-sécurité)
- [Exigences](#exigences)
- [Licence](#licence)

---

## Pourquoi Veribenim?

### ✅ Solution KVKK Indépendante du Framework

Fonctionne partout où PHP s'exécute:

- **PHP pur**: Intégration directe dans les applications PHP pures
- **Laravel**: Avec ServiceProvider et Facade
- **WordPress**: Intégration plugin ou SDK
- **Symfony**: Utilisation du SDK autonome
- **Websites statiques**: Combinaisons SSG + API
- **Applications héritées**: Compatible avec PHP 8.1+

### 🔒 Normes de Sécurité d'Entreprise

- **Chiffrement de bout en bout**: AES-256 (au repos), TLS 1.3+ (en transit)
- **Architecture zero-knowledge**: Les serveurs Veribenim ne peuvent pas stocker les données
- **Article 32 du RGPD**: Mesures de sécurité appropriées
- **Pistes d'audit**: Toutes les opérations sont enregistrées de façon sécurisée

### 📊 Support Complet DSAR

**Tous les Droits des Personnes selon les Articles 15-22 du RGPD:**

- Droit d'accès
- Droit à l'oubli
- Droit à la rectification
- Droit à la limitation du traitement
- Droit à la portabilité des données
- Droit d'opposition
- Droit contre les décisions automatisées

### ⚡ Dépendances Minimales

- **Seulement 3 fichiers**: VeribenimClient, Request, Response
- **Seulement ext-json et ext-curl**: Pas besoin de frameworks lourds
- **Mécanisme de fallback**: Utilise file_get_contents si curl indisponible
- **~50KB**: Léger et rapide

### 🚀 Haute Performance

- **API synchrone**: Appels non-bloquants
- **Mise en cache des requêtes**: Pas de requêtes dupliquées
- **Opérations par lot**: Envoyez plusieurs opérations dans une seule requête
- **Faible empreinte mémoire**: Ressources serveur minimales

---

## Démarrage rapide

```bash
composer require veribenim/php-sdk
```

```php
<?php
use Veribenim\VeribenimClient;

$client = new VeribenimClient(
    token: 'your_api_token',
    lang: 'fr',
    debug: false
);

$client->logFormConsent(
    formName: 'contact',
    consented: true,
    consentText: 'J\'accepte les conditions de confidentialité',
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

### Prérequis

- **PHP** 8.1+
- **ext-curl** ou **ext-json** (Standard)
- **Composer**
- Compte Veribenim

### Étapes d'installation

**1. Installation via Composer**

```bash
composer require veribenim/php-sdk
```

**2. Initialiser VeribenimClient**

```php
<?php
require_once 'vendor/autoload.php';

use Veribenim\VeribenimClient;

$client = new VeribenimClient(
    token: 'your_site_token_here',
    lang: 'fr',
    debug: false
);
```

**3. Variables d'environnement (optionnel)**

```php
$client = new VeribenimClient(
    token: $_ENV['VERIBENIM_TOKEN'],
    lang: $_ENV['VERIBENIM_LANG'] ?? 'fr',
    debug: $_ENV['APP_DEBUG'] ?? false
);
```

### Intégrations Framework

**Laravel (ServiceProvider)**

```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->singleton(VeribenimClient::class, function () {
        return new VeribenimClient(
            token: config('veribenim.token'),
            lang: 'fr',
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
            $lang: 'fr'
            $debug: '%kernel.debug%'
```

**WordPress (Plugin)**

```php
$veribenim = new VeribenimClient(
    token: get_option('veribenim_token'),
    lang: 'fr'
);

add_action('wp_footer', function() use ($veribenim) {
    $veribenim->logImpression();
});
```

---

## Utilisation

### Classe VeribenimClient

Classe principale pour toutes les opérations:

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

### Suivi du Consentement aux Cookies

#### logFormConsent — Soumission de Formulaire

```php
$client->logFormConsent(
    formName: 'newsletter_signup',
    consented: true,
    consentText: 'Je consens au traitement de mes données',
    metadata: ['email' => 'user@example.com']
);
```

**Paramètres:**

| Paramètre | Type | Description |
|---|---|---|
| `formName` | string | Identifiant du formulaire |
| `consented` | bool | Statut du consentement |
| `consentText` | string | Texte affiché |
| `metadata` | array | Données supplémentaires optionnelles |

#### getPreferences — Obtenir les Préférences

```php
$preferences = $client->getPreferences();

if ($preferences['analytics']) {
    echo "<script>gtag('config', 'GA_ID');</script>";
}
```

#### savePreferences — Enregistrer les Préférences

```php
$client->savePreferences([
    'necessary' => true,
    'analytics' => true,
    'marketing' => false,
    'preferences' => true
]);
```

#### logImpression — Visite de Page

```php
$client->logImpression();
```

#### logConsent — Enregistrement Manuel du Consentement

```php
$client->logConsent([
    'categories' => ['analytics', 'marketing'],
    'language' => 'fr',
    'consent_timestamp' => date('c')
]);
```

### Opérations DSAR

#### submitDsar — Droits des Personnes

```php
$response = $client->submitDsar(
    requestType: 'erasure',
    fullName: 'John Doe',
    email: 'john@example.com',
    description: 'Supprimer toutes mes données'
);
```

**Types DSAR Supportés:**

| Type | Article RGPD | Description |
|---|---|---|
| `access` | Article 15 | Droit d'accès |
| `erasure` | Article 17 | Droit à l'oubli |
| `rectification` | Article 16 | Droit à la rectification |
| `restriction` | Article 18 | Droit à la limitation |
| `portability` | Article 20 | Droit à la portabilité |
| `objection` | Article 21 | Droit d'opposition |
| `automated` | Article 22 | Droit contre les décisions automatisées |

### Intégration de Banneau de Cookies

**Option 1: Banneau HTML manuel**

```html
<div id="cookie-banner">
    <p>Nous utilisons des cookies pour les services essentiels.</p>
    <button onclick="saveCookiePreferences()">Accepter</button>
</div>
```

**Option 2: Bundle Veribenim (recommandé)**

```html
<script src="https://bundles.veribenim.com/your-site-token.js"></script>
```

---

## Normes de Sécurité

### Conformité RGPD

- **Article 6** — Base juridique
- **Article 7** — Conditions du consentement
- **Article 25** — Confidentialité par conception
- **Article 32** — Mesures de sécurité
- **Directives du CEPD** — Cookies et suivi

### Chiffrement des Données

| Composant | Technologie | Norme |
|---|---|---|
| Base de données | AES-256-GCM | FIPS 140-2 |
| Transmission | TLS 1.3+ | RFC 8446 |
| Clés | ECDH P-256 | NIST SP 800-56A |
| Signature | HMAC-SHA256 | RFC 2104 |

### Anonymisation des adresses IP

```
IP réelle:      192.168.1.42
Anonymisée:     192.168.0.0

IPv6:           2001:db8::1
Anonymisée:     2001:db8::0
```

### Journaux d'audit

- Décisions de consentement
- Soumissions de formulaires
- Demandes DSAR
- Suppression de données
- Appels API

Conservés minimum **3 ans**.

### Certifications de Sécurité

- ✓ ISO 27001
- ✓ ISO 27018
- ✓ SOC 2 Type II
- ✓ Conforme RGPD
- ✓ Tests de pénétration annuels

---

## Exigences

### Exigences Logicielles

```
PHP >= 8.1
ext-json (standard)
ext-curl (recommandé) ou ext-stream (fallback)
Composer
```

### Plateformes Supportées

| Plateforme | Version | Remarques |
|---|---|---|
| PHP pur | 8.1+ | Support complet |
| Laravel | 10/11/12 | Avec ServiceProvider |
| Symfony | 6.x+ | Injection de dépendance |
| WordPress | 6.0+ | Plugin ou SDK |
| PHP statique | Any | Intégration directe |

### Configuration Production

```php
$client = new VeribenimClient(
    token: getenv('VERIBENIM_TOKEN'),
    lang: 'fr',
    debug: false,
    timeout: 10,
    retry: 3
);
```

---

## Licence

MIT License

---

**Développé avec passion par Veribenim**

Web: [https://veribenim.com](https://veribenim.com)
Email: [support@veribenim.com](mailto:support@veribenim.com)
