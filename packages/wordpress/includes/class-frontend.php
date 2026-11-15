<?php

declare(strict_types=1);

if (! defined('ABSPATH')) exit;

class Veribenim_Frontend
{
    /**
     * Varsayılan adresler — bugünkü bulut kurulumu. Kurum içi (On-Prem)
     * kurulumda `veribenim_api_url` / `veribenim_bundle_base_url` seçenekleri
     * ya da aynı adlı filtrelerle değiştirilir.
     */
    public const DEFAULT_API_URL = 'https://live.veribenim.com';
    public const DEFAULT_BUNDLE_BASE_URL = 'https://bundles.veribenim.com';
    /** Eski sürümlerin script_url seçeneğine yazdığı yer tutucu. */
    public const LEGACY_PLACEHOLDER_SCRIPT_URL = 'https://bundles.veribenim.com/bundle.js';

    /**
     * API tabanı: seçenek → `veribenim_api_url` filtresi. Sondaki "/" atılır.
     */
    public static function api_url(): string
    {
        $url = (string) get_option('veribenim_api_url', self::DEFAULT_API_URL);
        $url = (string) apply_filters('veribenim_api_url', $url !== '' ? $url : self::DEFAULT_API_URL);

        return rtrim($url !== '' ? $url : self::DEFAULT_API_URL, '/');
    }

    /**
     * Bundle dosyalarının tabanı: seçenek → `veribenim_bundle_base_url` filtresi.
     *
     * Örnek (wp-config.php ya da mu-plugin):
     *   add_filter('veribenim_bundle_base_url', fn () => 'https://cdn.kurum.local/bundles');
     */
    public static function bundle_base_url(): string
    {
        $url = (string) get_option('veribenim_bundle_base_url', self::DEFAULT_BUNDLE_BASE_URL);
        $url = (string) apply_filters('veribenim_bundle_base_url', $url !== '' ? $url : self::DEFAULT_BUNDLE_BASE_URL);

        return rtrim($url !== '' ? $url : self::DEFAULT_BUNDLE_BASE_URL, '/');
    }

    /**
     * Banner scriptini WordPress script kuyruğuna ekler (<head>).
     * wp_enqueue_scripts hook'una bağlıdır.
     */
    public function enqueue_script(): void
    {
        $token = get_option('veribenim_token', '');

        if (empty($token)) return;

        // Admin ve WP-Cron isteklerinde yükleme
        if (is_admin() || (defined('DOING_CRON') && DOING_CRON)) return;

        $script_url = $this->resolve_bundle_url();
        if (empty($script_url)) return;

        // Sürüm null: bundle kendi cache-busting'ini yapar. in_footer=false → <head>.
        wp_enqueue_script('veribenim-banner', $script_url, [], null, false);
    }

    /**
     * veribenim-banner script tag'ine async/defer ve data-veribenim ekler.
     * script_loader_tag filtresine bağlıdır.
     */
    public function add_script_attributes(string $tag, string $handle): string
    {
        if ($handle !== 'veribenim-banner') {
            return $tag;
        }

        $token = esc_attr((string) get_option('veribenim_token', ''));

        return str_replace(
            ' src=',
            ' async defer data-veribenim="' . $token . '" src=',
            $tag
        );
    }

    /**
     * Bundle URL'ini domain'den türetir. Frontend enjeksiyonu ve admin
     * "Entegrasyon Kodu" gösterimi için TEK kaynak.
     * Öncelik: veribenim_script_url (manuel override) > veribenim_domain > site URL
     */
    public function resolve_bundle_url(): string
    {
        // Manuel override varsa kullan
        $custom_url = get_option('veribenim_script_url', '');
        if (!empty($custom_url) && $custom_url !== self::LEGACY_PLACEHOLDER_SCRIPT_URL) {
            return $custom_url;
        }

        // Domain ayarından veya site URL'inden türet
        $domain = get_option('veribenim_domain', '');
        if (empty($domain)) {
            $domain = home_url();
        }

        $filename = self::clean_domain_for_filename($domain);
        return self::bundle_base_url() . "/{$filename}.js";
    }

    /**
     * Domain'den bundle dosya adını türetir.
     * Backend CookieBundleService::cleanDomainForFilename() ile aynı mantık.
     */
    public static function clean_domain_for_filename(string $url): string
    {
        $domain = preg_replace('(^https?://)', '', $url);
        $domain = preg_replace('/^www\./', '', $domain);
        $domain = preg_replace('/[^a-z0-9]/', '', strtolower($domain));
        return $domain ?: 'bundle';
    }
}
