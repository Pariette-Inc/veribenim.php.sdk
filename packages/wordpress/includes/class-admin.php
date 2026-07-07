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
            'sanitize_callback' => [$this, 'sanitize_token'],
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
        // Token gizli tutulur — maskeleme ile shoulder-surfing riski azaltılır
        printf(
            '<input type="password" name="veribenim_token" value="%s" class="regular-text" placeholder="Environment token" autocomplete="off" />',
            esc_attr((string) get_option('veribenim_token', ''))
        );
        echo '<p class="description">' . esc_html__('Veribenim panelinden > Siteniz > Entegrasyon bölümünden alın.', 'veribenim') . '</p>';
    }

    /**
     * Token sanitizasyonu ve format doğrulaması.
     * Boş bırakılırsa mevcut değer korunur.
     */
    public function sanitize_token(string $value): string
    {
        $value = sanitize_text_field(trim($value));

        if ($value === '') {
            return ''; // Temizlenmek isteniyor
        }

        // Token alfanümerik ve 20–128 karakter arası olmalı
        if (! preg_match('/^[a-zA-Z0-9_\-]{20,128}$/', $value)) {
            add_settings_error(
                'veribenim_token',
                'invalid_format',
                __('Geçersiz token formatı. Token alfanümerik, 20-128 karakter arası olmalıdır.', 'veribenim'),
                'error'
            );
            // Hatalı değer kabul edilmez — eski değer korunur
            return (string) get_option('veribenim_token', '');
        }

        return $value;
    }

    /**
     * AJAX: Bağlantı testi — nonce doğrulamalı.
     * wp_ajax_veribenim_test_connection action'ına bağlıdır.
     */
    public function handle_test_connection(): void
    {
        // CSRF koruması
        check_ajax_referer('veribenim_test_connection', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Yetersiz yetki.', 'veribenim')], 403);
        }

        $token  = get_option('veribenim_token', '');
        $apiUrl = rtrim((string) get_option('veribenim_api_url', 'https://live.veribenim.com'), '/');

        if (empty($token)) {
            wp_send_json_error(['message' => __('Token girilmemiş.', 'veribenim')]);
        }

        $domain   = wp_parse_url(home_url(), PHP_URL_HOST) ?: '';
        $response = wp_remote_get(
            $apiUrl . '/api/public/verify/' . rawurlencode($domain),
            ['timeout' => 8, 'sslverify' => true]
        );

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            wp_send_json_success(['message' => __('Bağlantı başarılı.', 'veribenim'), 'code' => $code]);
        } else {
            wp_send_json_error(['message' => __('Bağlantı hatası.', 'veribenim'), 'code' => $code]);
        }
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
                    <p><?php esc_html_e('Veribenim token henüz girilmemiş. Aşağıya Veribenim panelinizdeki token\'ı girin.', 'veribenim'); ?></p>
                </div>
            <?php else: ?>
                <div class="notice notice-success">
                    <p><?php esc_html_e('Veribenim aktif ve çalışıyor.', 'veribenim'); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('veribenim_settings');
                do_settings_sections('veribenim');
                submit_button(__('Ayarları Kaydet', 'veribenim'));
                ?>
            </form>

            <?php if (!empty($token)): ?>
            <p>
                <button type="button" id="vb-test-connection" class="button button-secondary">
                    <?php esc_html_e('Bağlantıyı Test Et', 'veribenim'); ?>
                </button>
                <span id="vb-test-result" style="margin-left:10px;"></span>
            </p>
            <script>
            document.getElementById('vb-test-connection').addEventListener('click', function() {
                var btn = this;
                var result = document.getElementById('vb-test-result');
                btn.disabled = true;
                result.textContent = '<?php echo esc_js(__('Test ediliyor...', 'veribenim')); ?>';

                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=veribenim_test_connection&nonce=<?php echo esc_js(wp_create_nonce('veribenim_test_connection')); ?>'
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    btn.disabled = false;
                    if (res.success) {
                        result.style.color = '#00a32a';
                        result.textContent = '✓ ' + res.data.message;
                    } else {
                        result.style.color = '#d63638';
                        result.textContent = '✗ ' + (res.data ? res.data.message : '<?php echo esc_js(__('Hata oluştu.', 'veribenim')); ?>');
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    result.style.color = '#d63638';
                    result.textContent = '✗ <?php echo esc_js(__('İstek gönderilemedi.', 'veribenim')); ?>';
                });
            });
            </script>
            <?php endif; ?>

            <hr>
            <h2><?php esc_html_e('Entegrasyon Kodu', 'veribenim'); ?></h2>
            <?php if (!empty($token)): ?>
                <p><?php esc_html_e('Bu kod otomatik olarak tüm sayfalara eklenmektedir:', 'veribenim'); ?></p>
                <code style="display:block;padding:12px;background:#f0f0f1;border-left:4px solid #2271b1;">
                    <?php
                    $frontend = new Veribenim_Frontend();
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

        echo '<div class="notice notice-warning is-dismissible"><p>';
        printf(
            /* translators: %s: Veribenim ayarlar sayfasının URL'i */
            wp_kses(
                __('<strong>Veribenim:</strong> Token girilmemiş. <a href="%s">Ayarları yapılandırın</a>.', 'veribenim'),
                ['strong' => [], 'a' => ['href' => []]]
            ),
            esc_url(admin_url('options-general.php?page=veribenim'))
        );
        echo '</p></div>';
    }
}
