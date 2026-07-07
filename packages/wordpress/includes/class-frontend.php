<?php

declare(strict_types=1);

if (! defined('ABSPATH')) exit;

class Veribenim_Frontend
{
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
     * Bundle URL'ini domain'den türetir.
     * Öncelik: veribenim_script_url (manuel override) > veribenim_domain > site URL
     */
    private function resolve_bundle_url(): string
    {
        // Manuel override varsa kullan
        $custom_url = get_option('veribenim_script_url', '');
        if (!empty($custom_url) && $custom_url !== 'https://bundles.veribenim.com/bundle.js') {
            return $custom_url;
        }

        // Domain ayarından veya site URL'inden türet
        $domain = get_option('veribenim_domain', '');
        if (empty($domain)) {
            $domain = home_url();
        }

        $filename = self::clean_domain_for_filename($domain);
        return "https://bundles.veribenim.com/{$filename}.js";
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
