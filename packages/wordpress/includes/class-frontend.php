<?php

declare(strict_types=1);

if (! defined('ABSPATH')) exit;

class Veribenim_Frontend
{
    /**
     * Banner scriptini <head> içine enjekte eder.
     * wp_head hook'una bağlıdır (priority: 1 — mümkün olduğunca erken).
     */
    public function inject_script(): void
    {
        $token = get_option('veribenim_token', '');

        if (empty($token)) return;

        // Admin, login ve WP-Cron sayfalarında yükleme
        if (is_admin() || (defined('DOING_CRON') && DOING_CRON)) return;

        $script_url = $this->resolve_bundle_url();
        if (empty($script_url)) return;

        $token_attr = esc_attr($token);

        printf(
            '<script src="%s" async defer data-veribenim="%s"></script>' . PHP_EOL,
            esc_url($script_url),
            $token_attr
        );
    }

    /**
     * Bundle URL'ini domain'den türetir.
     */
    private function resolve_bundle_url(): string
    {
        $custom_url = get_option('veribenim_script_url', '');
        if (!empty($custom_url) && $custom_url !== 'https://bundles.veribenim.com/bundle.js') {
            return $custom_url;
        }

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
