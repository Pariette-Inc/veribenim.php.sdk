<?php
/**
 * Eklenti "Sil" ile kaldırıldığında çalışır.
 * Veribenim'in wp_options içindeki tüm ayarlarını temizler.
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$veribenim_options = [
    'veribenim_token',
    'veribenim_domain',
    'veribenim_lang',
    'veribenim_api_url',
    'veribenim_script_url',
];

foreach ($veribenim_options as $veribenim_option) {
    delete_option($veribenim_option);
}

// Multisite: her site için de temizle
if (is_multisite()) {
    $veribenim_site_ids = get_sites(['fields' => 'ids']);
    foreach ($veribenim_site_ids as $veribenim_site_id) {
        switch_to_blog((int) $veribenim_site_id);
        foreach ($veribenim_options as $veribenim_option) {
            delete_option($veribenim_option);
        }
        restore_current_blog();
    }
}
