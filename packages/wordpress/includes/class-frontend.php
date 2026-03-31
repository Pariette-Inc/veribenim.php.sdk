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

        $script_url = esc_url(get_option('veribenim_script_url', 'https://bundles.veribenim.com/bundle.js'));
        $lang       = esc_attr(get_option('veribenim_lang', 'tr'));
        $token_attr = esc_attr($token);

        printf(
            '<script src="%s?token=%s&lang=%s" async defer data-veribenim="%s"></script>' . PHP_EOL,
            $script_url,
            $token_attr,
            $lang,
            $token_attr
        );
    }
}
