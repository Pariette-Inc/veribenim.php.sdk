=== Veribenim KVKK & GDPR Çerez Yönetimi ===
Contributors: pariette
Tags: kvkk, gdpr, cookie-consent, privacy, consent
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.4.0
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
* Google Consent Mode v2 ve IAB TCF v2.2 sinyalleri (bundle üzerinden).

Kullanmak için bir [Veribenim](https://veribenim.com) hesabı ve ortam (environment) token'ı gerekir.

== External services ==

Bu eklenti, çalışmak için Veribenim platformuna (Pariette tarafından işletilir) bağlanır. Aşağıdaki harici istekler yapılır:

1. **Banner script'i** — `https://bundles.veribenim.com/{site-domain}.js` adresinden ziyaretçi tarayıcısına çerez onay banner'ı yüklenir. Bu istek sırasında ziyaretçinin IP adresi ve tarayıcı bilgisi (user agent) CDN'e iletilir.
2. **Bağlantı testi** — Yönetici panelindeki "Bağlantıyı Test Et" butonu, site domain'inizi `https://live.veribenim.com/api/public/verify/{domain}` adresine gönderir.
3. **Form şeması ve gönderimleri** — `[veribenim_form]` kısa kodu kullanılıyorsa, form şeması `https://live.veribenim.com` üzerinden çekilir ve ziyaretçinin form yanıtları (girilen tüm alanlar) aynı adrese gönderilir.
4. **Rıza kayıtları** — Ziyaretçi banner üzerinden rıza kararı verdiğinde bu karar, banner script'i tarafından Veribenim API'sine kaydedilir (session kimliği, rıza kategorileri, maskelenmiş IP).

Bu veriler KVKK/GDPR rıza kayıtlarının yasal saklama yükümlülüğü kapsamında Veribenim altyapısında saklanır.

* Hizmet: [Veribenim](https://veribenim.com)
* Kullanım şartları: https://veribenim.com/legal/operasyon-kosullari
* Gizlilik politikası: https://veribenim.com/gizlilik-politikasi

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

= Eklenti hangi harici servislere bağlanır? =

Banner script'i `bundles.veribenim.com` adresinden yüklenir; rıza kayıtları, bağlantı testi ve form gönderimleri `live.veribenim.com` API'sine gider. Ayrıntılar "External services" bölümündedir.

= Eklentiyi silersem verilerime ne olur? =

Eklenti silindiğinde WordPress'teki tüm Veribenim ayarları (token dahil) temizlenir. Platformdaki rıza kayıtlarınız Veribenim hesabınızda kalır.

== Changelog ==

= 0.4.0 =
* WordPress.org gönderim hazırlığı: harici servis bildirimi, uninstall temizliği, çeviri şablonu (.pot).
* Eklenti silindiğinde tüm veribenim_* ayarları otomatik temizlenir (multisite dahil).
* `languages/veribenim.pot` çeviri şablonu eklendi.
* Kullanılmayan REST API placeholder'ı kaldırıldı.

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

= 0.4.0 =
WordPress.org uyumluluk sürümü. Eklenti silme işlemi artık tüm ayarları temizler; token'ınızı not almadan silmeyin.

= 0.3.0 =
Rıza veri modeli platform kategori anahtarlarıyla hizalandı. Özel entegrasyonlar için kategori anahtarlarını (strictly_necessary, functional, analytics, marketing) güncelleyin.
