<?php

declare(strict_types=1);

if (! defined('ABSPATH')) exit;

class Veribenim_Admin
{
    public function add_menu_page(): void
    {
        add_options_page(
            page_title: __('Veribenim Ayarları', 'veribenim'),
            menu_title: 'Veribenim',
            capability: 'manage_options',
            menu_slug:  'veribenim',
            callback:   [$this, 'render_settings_page'],
        );
    }

    public function register_settings(): void
    {
        register_setting('veribenim_settings', 'veribenim_token', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ]);

        register_setting('veribenim_settings', 'veribenim_lang', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'tr',
        ]);

        register_setting('veribenim_settings', 'veribenim_api_url', [
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => 'https://live.veribenim.com',
        ]);

        register_setting('veribenim_settings', 'veribenim_script_url', [
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => 'https://bundles.veribenim.com/bundle.js',
        ]);

        // --- Section ---
        add_settings_section(
            id:       'veribenim_main',
            title:    __('Bağlantı Ayarları', 'veribenim'),
            callback: '__return_false',
            page:     'veribenim',
        );

        add_settings_field(
            id:       'veribenim_token',
            title:    __('Site Token', 'veribenim'),
            callback: [$this, 'render_token_field'],
            page:     'veribenim',
            section:  'veribenim_main',
        );

        add_settings_field(
            id:       'veribenim_lang',
            title:    __('Dil', 'veribenim'),
            callback: [$this, 'render_lang_field'],
            page:     'veribenim',
            section:  'veribenim_main',
        );
    }

    public function render_token_field(): void
    {
        $token = esc_attr(get_option('veribenim_token', ''));
        echo '<input type="text" name="veribenim_token" value="' . $token . '" class="regular-text" placeholder="32 karakterlik environment token" />';
        echo '<p class="description">' . __('Veribenim panelinden > Siteniz > Entegrasyon bölümünden alın.', 'veribenim') . '</p>';
    }

    public function render_lang_field(): void
    {
        $lang = get_option('veribenim_lang', 'tr');
        echo '<select name="veribenim_lang">';
        echo '<option value="tr"' . selected($lang, 'tr', false) . '>Türkçe</option>';
        echo '<option value="en"' . selected($lang, 'en', false) . '>English</option>';
        echo '</select>';
    }

    public function render_settings_page(): void
    {
        if (! current_user_can('manage_options')) return;

        $token = get_option('veribenim_token', '');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php if (empty($token)): ?>
                <div class="notice notice-warning">
                    <p><?php _e('Veribenim token henüz girilmemiş. Aşağıya Veribenim panelinizdeki token\'ı girin.', 'veribenim'); ?></p>
                </div>
            <?php else: ?>
                <div class="notice notice-success">
                    <p><?php _e('Veribenim aktif ve çalışıyor.', 'veribenim'); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('veribenim_settings');
                do_settings_sections('veribenim');
                submit_button(__('Ayarları Kaydet', 'veribenim'));
                ?>
            </form>

            <hr>
            <h2><?php _e('Entegrasyon Kodu', 'veribenim'); ?></h2>
            <?php if (!empty($token)): ?>
                <p><?php _e('Bu kod otomatik olarak tüm sayfalara eklenmektedir:', 'veribenim'); ?></p>
                <code style="display:block;padding:12px;background:#f0f0f1;border-left:4px solid #2271b1;">
                    <?php
                    $domain = get_option('veribenim_domain', home_url());
                    $filename = Veribenim_Frontend::clean_domain_for_filename($domain);
                    $bundle_url = "https://bundles.veribenim.com/{$filename}.js";
                    ?>
                    &lt;script src="<?php echo esc_html($bundle_url); ?>" async defer&gt;&lt;/script&gt;
                </code>
            <?php endif; ?>
        </div>
        <?php
    }

    public function maybe_show_setup_notice(): void
    {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'settings_page_veribenim') return;

        $token = get_option('veribenim_token', '');
        if (!empty($token)) return;

        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p>' . sprintf(
            __('<strong>Veribenim:</strong> Token girilmemiş. <a href="%s">Ayarları yapılandırın</a>.', 'veribenim'),
            admin_url('options-general.php?page=veribenim')
        ) . '</p>';
        echo '</div>';
    }
}
