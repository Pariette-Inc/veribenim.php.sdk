=== Veribenim KVKK & GDPR Çerez Yönetimi ===
Contributors: pariette
Tags: cookie-consent, gdpr, privacy, consent, cookie-banner
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.4.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Add a KVKK- and GDPR-compliant cookie consent banner to your site and manage visitor consent records through your Veribenim account.

== Description ==

Veribenim adds a KVKK- and GDPR-compliant cookie consent banner to your WordPress site and stores visitor consent records in the Veribenim platform.

Features:

* Automatic injection of the cookie consent banner (Veribenim bundle).
* Visitor consent categories: strictly necessary, functional, analytics, marketing.
* Render KVKK/contact forms server-side or via JavaScript with the `[veribenim_form]` shortcode.
* Token validation and connection testing from the admin panel.
* Integration with the cookie scanner, preference center, and DSAR flows in your Veribenim account.
* Google Consent Mode v2 and IAB TCF v2.2 signals (through the bundle).

A [Veribenim](https://veribenim.com) account and an environment token are required to use this plugin.

== External services ==

This plugin connects to the Veribenim platform (operated by Pariette) in order to function. The following external requests are made:

1. **Banner script** — the cookie consent banner is loaded into the visitor's browser from `https://bundles.veribenim.com/{site-domain}.js`. During this request the visitor's IP address and user agent are sent to the CDN.
2. **Connection test** — the "Test connection" button in the admin panel sends your site domain to `https://live.veribenim.com/api/public/verify/{domain}`.
3. **Form schema and submissions** — when the `[veribenim_form]` shortcode is used, the form schema is fetched from `https://live.veribenim.com` and the visitor's form responses (all submitted fields) are sent to the same host.
4. **Consent records** — when a visitor makes a consent decision through the banner, that decision is recorded to the Veribenim API by the banner script (session id, consent categories, masked IP).

These records are stored on Veribenim infrastructure to meet the legal retention obligations for KVKK/GDPR consent records.

* Service: [Veribenim](https://veribenim.com)
* Terms of service: https://veribenim.com/legal/operasyon-kosullari
* Privacy policy: https://veribenim.com/gizlilik-politikasi

== Installation ==

1. Install and activate the plugin.
2. Go to **Settings → Veribenim**.
3. Enter the environment token from your Veribenim panel (Your site → Integration).
4. Verify with "Test connection". The banner is added to your site automatically.

== Frequently Asked Questions ==

= Is a Veribenim account required? =

Yes. The banner and consent records are tied to the Veribenim platform; you can create a free account and obtain a token.

= Where is my data stored? =

Consent records and visitor preferences are stored on Veribenim infrastructure (in Türkiye).

= Which external services does the plugin connect to? =

The banner script is loaded from `bundles.veribenim.com`; consent records, connection tests, and form submissions go to the `live.veribenim.com` API. See the "External services" section for details.

= What happens to my data if I delete the plugin? =

When the plugin is deleted, all Veribenim settings in WordPress (including the token) are removed. Your consent records on the platform remain in your Veribenim account.

== Changelog ==

= 0.4.0 =
* WordPress.org submission readiness: external service disclosure, uninstall cleanup, translation template (.pot).
* Banner script now loaded through wp_enqueue_script.
* All output escaped and internationalization functions hardened per Plugin Check.
* All veribenim_* settings are removed automatically on uninstall (including multisite).

= 0.3.0 =
* SDK support for cookie scanning, web analytics, and domain verification endpoints.
* Consent data model aligned with the platform (category keys: strictly_necessary, functional, analytics, marketing).
* `[veribenim_form]` shortcode, connection test, and token validation.
* Various stability fixes.

= 0.2.0 =
* Interim release.

= 0.1.0 =
* Initial release.

== Upgrade Notice ==

= 0.4.0 =
WordPress.org compliance release. Deleting the plugin now removes all settings; note down your token before deleting.

= 0.3.0 =
Consent data model aligned with platform category keys. Update your custom integrations to the category keys (strictly_necessary, functional, analytics, marketing).
