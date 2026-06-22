=== Veribenim KVKK & GDPR Çerez Yönetimi ===
Contributors: pariette
Tags: kvkk, gdpr, cookie-consent, privacy, consent
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.1
Stable tag: 0.3.0
License: MIT
License URI: https://opensource.org/licenses/MIT

KVKK ve GDPR uyumlu çerez onay banner'ı. Veribenim hesabınızla bağlayın, çerez yönetimini ve rıza kayıtlarını otomatikleştirin.

== Description ==

Veribenim WordPress eklentisi, sitenize KVKK ve GDPR uyumlu bir çerez onay banner'ı ekler ve ziyaretçi rıza kayıtlarını Veribenim platformunda saklar.

Özellikler:

* Çerez onay banner'ının otomatik enjeksiyonu (Veribenim bundle).
* Ziyaretçi rıza kategorileri: zorunlu, işlevsel, analitik, pazarlama.
* `[veribenim_form]` kısa kodu ile KVKK/iletişim formlarını sunucu tarafında veya JS ile render etme.
* Yönetici panelinde token doğrulaması ve bağlantı testi.
* Veribenim hesabınızdaki çerez taraması, tercih merkezi ve DSAR akışlarıyla entegrasyon.

Kullanmak için bir [Veribenim](https://veribenim.com) hesabı ve ortam (environment) token'ı gerekir.

== Installation ==

1. Eklentiyi yükleyin ve etkinleştirin.
2. **Ayarlar → Veribenim** sayfasına gidin.
3. Veribenim panelinizden aldığınız ortam token'ını girin (Siteniz → Entegrasyon).
4. "Bağlantıyı test et" ile doğrulayın. Banner siteye otomatik eklenir.

== Frequently Asked Questions ==

= Veribenim hesabı gerekli mi? =

Evet. Banner ve rıza kayıtları Veribenim platformuna bağlıdır; ücretsiz hesap oluşturup token alabilirsiniz.

= Verilerim nerede saklanıyor? =

Rıza kayıtları ve ziyaretçi tercihleri Veribenim altyapısında (Türkiye'de) saklanır.

== Changelog ==

= 0.3.0 =
* Çerez tarama, web analytics ve domain doğrulama uçları için SDK desteği.
* Rıza veri modeli platformla hizalandı (kategori anahtarları: strictly_necessary, functional, analytics, marketing).
* `[veribenim_form]` kısa kodu, bağlantı testi ve token doğrulaması.
* Çeşitli kararlılık düzeltmeleri.

= 0.2.0 =
* Ara sürüm.

= 0.1.0 =
* İlk sürüm.

== Upgrade Notice ==

= 0.3.0 =
Rıza veri modeli platform kategori anahtarlarıyla hizalandı. Özel entegrasyonlar için kategori anahtarlarını (strictly_necessary, functional, analytics, marketing) güncelleyin.
