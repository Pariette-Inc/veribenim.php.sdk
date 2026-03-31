<?php

declare(strict_types=1);

if (! defined('ABSPATH')) exit;

class Veribenim_Plugin
{
    private Veribenim_Admin $admin;
    private Veribenim_Frontend $frontend;

    public function __construct()
    {
        $this->admin    = new Veribenim_Admin();
        $this->frontend = new Veribenim_Frontend();
    }

    public function run(): void
    {
        // Admin
        if (is_admin()) {
            add_action('admin_menu', [$this->admin, 'add_menu_page']);
            add_action('admin_init', [$this->admin, 'register_settings']);
            add_action('admin_notices', [$this->admin, 'maybe_show_setup_notice']);
        }

        // Frontend
        add_action('wp_head', [$this->frontend, 'inject_script'], 1);

        // Dil desteği
        add_action('init', function () {
            load_plugin_textdomain('veribenim', false, dirname(plugin_basename(VERIBENIM_PLUGIN_FILE)) . '/languages');
        });

        // REST API endpoint (opsiyonel — JS bundle ile doğrudan iletişim için)
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function register_rest_routes(): void
    {
        // Gelecek sürüm için placeholder
    }
}
