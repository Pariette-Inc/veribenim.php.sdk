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
            // AJAX: bağlantı testi — nonce doğrulamalı (class-admin.php handle_test_connection)
            add_action('wp_ajax_veribenim_test_connection', [$this->admin, 'handle_test_connection']);
        }

        // Frontend
        add_action('wp_head', [$this->frontend, 'inject_script'], 1);

        // Form Generator shortcode: [veribenim_form slug="iletisim-formu"]
        add_shortcode('veribenim_form', [$this, 'render_form_shortcode']);

        // Dil desteği
        add_action('init', function () {
            load_plugin_textdomain('veribenim', false, dirname(plugin_basename(VERIBENIM_PLUGIN_FILE)) . '/languages');
        });

        // REST API endpoint (opsiyonel — JS bundle ile doğrudan iletişim için)
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * [veribenim_form] shortcode handler
     *
     * Kullanım:
     *   [veribenim_form slug="iletisim-formu"]
     *   [veribenim_form slug="iletisim-formu" class="my-form" id="contact"]
     *   [veribenim_form slug="iletisim-formu" mode="js"]  ← sadece JS ile render
     *
     * Modlar:
     *   html (varsayılan) — Sunucu taraflı HTML render, hızlı ve SEO dostu.
     *                        Koşullu mantık ve çok adımlı formlar JS gerektirir.
     *   js               — Yalnızca container div üretir; veribenim bundle/SDK render eder.
     *                        Tam etkileşim için bundle script'in sayfada yüklü olması gerekir.
     */
    public function render_form_shortcode(array $atts): string
    {
        $atts = shortcode_atts([
            'slug'  => '',
            'class' => 'vb-form',
            'id'    => '',
            'mode'  => 'html', // html | js
            'lang'  => '',     // dil kodu: tr, en, de, fr, es, bg, ar
        ], $atts, 'veribenim_form');

        $slug = sanitize_text_field($atts['slug']);

        if (empty($slug)) {
            return '<!-- Veribenim: [veribenim_form] shortcode requires slug attribute -->';
        }

        $token = get_option('veribenim_token', '');
        if (empty($token)) {
            return '<!-- Veribenim: token not configured -->';
        }

        $lang = sanitize_text_field($atts['lang']);

        // JS modu: sadece container div döndür, bundle renderer halleder
        if ($atts['mode'] === 'js') {
            $containerId = !empty($atts['id']) ? esc_attr($atts['id']) : 'vb-form-' . esc_attr($slug);
            $langOption = $lang ? sprintf(',{lang:"%s"}', esc_js($lang)) : '';
            return sprintf(
                '<div id="%s" class="%s" data-vb-form="%s" data-vb-token="%s"></div>' .
                '<script>document.addEventListener("VeribenimReady",function(){if(window.veribenim&&veribenim.renderForm){veribenim.renderForm("%s","#%s"%s);}});</script>',
                $containerId,
                esc_attr($atts['class']),
                esc_attr($slug),
                esc_attr($token),
                esc_js($slug),
                esc_js($containerId),
                $langOption
            );
        }

        // HTML modu: PHP ile sunucu taraflı render
        $apiUrl = rtrim(get_option('veribenim_api_url', 'https://live.veribenim.com'), '/');

        // Schema'yı Veribenim API'sinden çek
        $schemaUrl = $apiUrl . '/api/public/forms/' . rawurlencode($token) . '/' . rawurlencode($slug);
        if (!empty($lang)) {
            $schemaUrl .= '?lang=' . rawurlencode($lang);
        }
        $response  = wp_remote_get($schemaUrl, ['timeout' => 8, 'sslverify' => true]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            if (current_user_can('manage_options')) {
                return '<p class="vb-error" style="color:#ef4444;font-size:0.875rem;">Veribenim: &quot;' . esc_html($slug) . '&quot; formu yüklenemedi. Token ve slug\'u kontrol edin.</p>';
            }
            return '';
        }

        $schema = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($schema)) {
            return '';
        }

        return $this->build_form_html($schema, $atts, $token, $apiUrl);
    }

    /**
     * Schema'dan form HTML'i üretir.
     *
     * @internal render_form_shortcode tarafından kullanılır.
     */
    private function build_form_html(array $schema, array $atts, string $token, string $apiUrl): string
    {
        $slug     = $schema['slug'] ?? $atts['slug'];
        $settings = $schema['settings'] ?? [];
        $fields   = $schema['fields'] ?? [];
        $type     = $schema['type'] ?? 'single_step';
        $submitText = esc_html($settings['submit_button_text'] ?? __('Gönder', 'veribenim'));
        $formAction = $apiUrl . '/api/public/forms/' . rawurlencode($token) . '/' . rawurlencode($slug);
        $formId   = !empty($atts['id']) ? esc_attr($atts['id']) : 'vb-form-' . esc_attr($slug);

        // Alanları sırala
        usort($fields, fn($a, $b) => (int)($a['order'] ?? 0) <=> (int)($b['order'] ?? 0));

        $html  = '<form id="' . $formId . '" class="' . esc_attr($atts['class']) . '"';
        $html .= ' data-vb-form="' . esc_attr($slug) . '"';
        $html .= ' data-vb-action="' . esc_url($formAction) . '"';
        $html .= ' data-vb-type="' . esc_attr($type) . '"';
        $html .= ' method="post" novalidate>';

        foreach ($fields as $field) {
            $html .= $this->build_field_html($field);
        }

        $html .= '<div class="vb-form-footer">';
        $html .= '<button type="submit" class="vb-submit">' . $submitText . '</button>';
        $html .= '<div class="vb-badge">';
        $html .= '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
        $html .= ' <span>' . esc_html__('VeriBenim ile kişisel verileriniz koruma altında', 'veribenim') . '</span>';
        $html .= '</div></div>';
        $html .= '</form>';

        // Inline submit handler (form JS bundle yüklü değilse çalışır)
        $html .= $this->inline_form_script($formId, $formAction, $settings);

        return $html;
    }

    /**
     * Tek bir form alanının HTML markup'ını üretir.
     */
    private function build_field_html(array $field): string
    {
        $type        = $field['type'] ?? 'input';
        $uuid        = esc_attr($field['uuid'] ?? '');
        $label       = esc_html($field['label'] ?? '');
        $placeholder = esc_attr($field['placeholder'] ?? '');
        $required    = !empty($field['required']);
        $helpText    = esc_html($field['help_text'] ?? '');
        $reqAttr     = $required ? ' required aria-required="true"' : '';
        $reqMark     = $required ? '<span class="vb-required" aria-hidden="true">*</span>' : '';

        if ($type === 'divider') return '<hr class="vb-divider">' . PHP_EOL;
        if ($type === 'heading') return '<div class="vb-heading">' . $label . '</div>' . PHP_EOL;

        $html  = '<div class="vb-field" data-field-uuid="' . $uuid . '">' . PHP_EOL;
        $html .= '<label class="vb-label" for="vb-' . $uuid . '">' . $label . $reqMark . '</label>' . PHP_EOL;

        switch ($type) {
            case 'textarea':
                $rows  = (int)($field['settings']['rows'] ?? 4);
                $html .= '<textarea id="vb-' . $uuid . '" name="' . $uuid . '" class="vb-textarea" placeholder="' . $placeholder . '" rows="' . $rows . '"' . $reqAttr . '></textarea>';
                break;

            case 'dropdown':
                $html .= '<select id="vb-' . $uuid . '" name="' . $uuid . '" class="vb-select"' . $reqAttr . '>';
                $html .= '<option value="">' . ($placeholder ?: esc_html__('Seçiniz...', 'veribenim')) . '</option>';
                foreach ($field['options'] ?? [] as $opt) {
                    $html .= '<option value="' . esc_attr($opt['value']) . '">' . esc_html($opt['label']) . '</option>';
                }
                $html .= '</select>';
                break;

            case 'radio':
                $html .= '<div class="vb-radio-group" role="group">';
                foreach ($field['options'] ?? [] as $opt) {
                    $html .= '<label class="vb-radio-item"><input type="radio" name="' . $uuid . '" value="' . esc_attr($opt['value']) . '"' . $reqAttr . '> ' . esc_html($opt['label']) . '</label>';
                }
                $html .= '</div>';
                break;

            case 'checkbox':
                $html .= '<div class="vb-checkbox-group" role="group">';
                foreach ($field['options'] ?? [] as $opt) {
                    $html .= '<label class="vb-checkbox-item"><input type="checkbox" name="' . $uuid . '[]" value="' . esc_attr($opt['value']) . '"> ' . esc_html($opt['label']) . '</label>';
                }
                $html .= '</div>';
                break;

            case 'file':
                $accept = implode(',', $field['validation']['file_types'] ?? []);
                $multi  = !empty($field['settings']['multiple']) ? ' multiple' : '';
                $html  .= '<input type="file" id="vb-' . $uuid . '" name="' . $uuid . '" class="vb-input"' . ($accept ? ' accept="' . esc_attr($accept) . '"' : '') . $multi . $reqAttr . '>';
                break;

            case 'date':
                $html .= '<input type="date" id="vb-' . $uuid . '" name="' . $uuid . '" class="vb-input"' . $reqAttr . '>';
                break;

            case 'number':
                $min  = isset($field['validation']['min']) ? ' min="' . (int)$field['validation']['min'] . '"' : '';
                $max  = isset($field['validation']['max']) ? ' max="' . (int)$field['validation']['max'] . '"' : '';
                $html .= '<input type="number" id="vb-' . $uuid . '" name="' . $uuid . '" class="vb-input" placeholder="' . $placeholder . '"' . $min . $max . $reqAttr . '>';
                break;

            case 'email':
                $html .= '<input type="email" id="vb-' . $uuid . '" name="' . $uuid . '" class="vb-input" placeholder="' . $placeholder . '"' . $reqAttr . '>';
                break;

            case 'phone':
                $html .= '<input type="tel" id="vb-' . $uuid . '" name="' . $uuid . '" class="vb-input" placeholder="' . $placeholder . '"' . $reqAttr . '>';
                break;

            default:
                $html .= '<input type="text" id="vb-' . $uuid . '" name="' . $uuid . '" class="vb-input" placeholder="' . $placeholder . '"' . $reqAttr . '>';
        }

        if ($helpText) {
            $html .= PHP_EOL . '<div class="vb-help">' . $helpText . '</div>';
        }

        $html .= PHP_EOL . '<div class="vb-error" data-error-for="' . $uuid . '" hidden aria-live="polite"></div>';
        $html .= PHP_EOL . '</div>' . PHP_EOL;

        return $html;
    }

    /**
     * Form submit'i AJAX ile Veribenim API'sine gönderen minimal script.
     * Yalnızca veribenim bundle yüklü değilse etkin olur.
     */
    private function inline_form_script(string $formId, string $formAction, array $settings): string
    {
        $successTitle   = esc_js($settings['success_title']   ?? __('Teşekkürler!', 'veribenim'));
        $successMessage = esc_js($settings['success_message'] ?? __('Formunuz başarıyla gönderildi.', 'veribenim'));

        // Redirect URL — yalnızca http(s) protokolüne izin ver.
        // javascript: veya data: URI'larını bloke et (open redirect + XSS koruması).
        $rawRedirect = $settings['redirect_url'] ?? '';
        $redirectUrl = (is_string($rawRedirect) && preg_match('/^https?:\/\//', $rawRedirect))
            ? esc_js($rawRedirect)
            : '';

        return <<<JS
<script>
(function(){
  var form = document.getElementById('{$formId}');
  if(!form || form.dataset.vbBound) return;
  form.dataset.vbBound = '1';
  form.addEventListener('submit', function(e){
    e.preventDefault();
    var btn = form.querySelector('[type=submit]');
    if(btn) btn.disabled = true;
    var fd = new FormData(form);
    var data = {};
    fd.forEach(function(v,k){ if(k.endsWith('[]')){var rk=k.slice(0,-2);data[rk]=data[rk]||[];data[rk].push(v);}else{data[k]=v;}});
    fetch('{$formAction}', {
      method: 'POST',
      headers: {'Content-Type':'application/json','Accept':'application/json'},
      body: JSON.stringify(data)
    })
    .then(function(r){ return r.json(); })
    .then(function(res){
      var t = res.success_title || '{$successTitle}';
      var m = res.success_message || '{$successMessage}';
      form.innerHTML = '<div class="vb-success" style="text-align:center;padding:32px"><div style="font-size:2rem;margin-bottom:12px">✅</div><div style="font-size:1.1rem;font-weight:700;margin-bottom:8px">'+t+'</div><div style="color:#6b7280">'+m+'</div><div class="vb-badge" style="display:flex;align-items:center;justify-content:center;gap:4px;margin-top:20px;font-size:0.7rem;color:#9ca3af"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> VeriBenim ile kişisel verileriniz koruma altında</div></div>';
      {$redirectUrl ? "setTimeout(function(){window.location.href='" . $redirectUrl . "';},2000);" : ''}
    })
    .catch(function(){
      if(btn) btn.disabled = false;
      alert('Form gönderilemedi. Lütfen tekrar deneyin.');
    });
  });
})();
</script>
JS;
    }

    public function register_rest_routes(): void
    {
        // Gelecek sürüm için placeholder
    }
}
