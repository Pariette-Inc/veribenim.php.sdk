<?php
/**
 * Plugin Name:       Veribenim KVKK & GDPR Çerez Yönetimi
 * Plugin URI:        https://veribenim.com
 * Description:       KVKK ve GDPR uyumlu çerez onay banner'ı. Veribenim hesabınızla bağlayın, çerez yönetimini otomatikleştirin.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Pariette
 * Author URI:        https://veribenim.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       veribenim
 * Domain Path:       /languages
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('VERIBENIM_VERSION', '0.1.0');
define('VERIBENIM_PLUGIN_FILE', __FILE__);
define('VERIBENIM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VERIBENIM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Composer autoload (bağımsız kurulum için)
if (file_exists(VERIBENIM_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once VERIBENIM_PLUGIN_DIR . 'vendor/autoload.php';
}

require_once VERIBENIM_PLUGIN_DIR . 'includes/class-plugin.php';
require_once VERIBENIM_PLUGIN_DIR . 'includes/class-admin.php';
require_once VERIBENIM_PLUGIN_DIR . 'includes/class-frontend.php';

/**
 * Plugin başlatma
 */
function veribenim_init(): void
{
    $plugin = new Veribenim_Plugin();
    $plugin->run();
}
add_action('plugins_loaded', 'veribenim_init');

/**
 * Aktivasyon
 */
register_activation_hook(__FILE__, function () {
    add_option('veribenim_token', '');
    add_option('veribenim_lang', 'tr');
    add_option('veribenim_api_url', 'https://api.veribenim.com');
    add_option('veribenim_script_url', 'https://bundles.veribenim.com/bundle.js');
});

/**
 * Deaktivasyon
 */
register_deactivation_hook(__FILE__, function () {
    // Ayarlar temizlenmez — kullanıcı isteğiyle silinir
});
