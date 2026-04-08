<?php

declare(strict_types=1);

namespace Veribenim;

class VeribenimClient
{
    private VeribenimConfig $config;

    public function __construct(VeribenimConfig|string $config)
    {
        if (is_string($config)) {
            $config = new VeribenimConfig(token: $config);
        }
        $this->config = $config;
    }

    // -------------------------------------------------------------------------
    // Sayfa görüntüleme
    // POST /api/impressions/{token}
    // -------------------------------------------------------------------------

    public function logImpression(array $payload = []): bool
    {
        $data = array_merge([
            'url'        => $_SERVER['REQUEST_URI'] ?? '',
            'referrer'   => $_SERVER['HTTP_REFERER'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ], $payload);

        return $this->post("/api/impressions/{$this->config->token}", $data) !== null;
    }

    // -------------------------------------------------------------------------
    // Onay kararı loglama
    // POST /api/consents/{token}/log
    // -------------------------------------------------------------------------

    /**
     * @param string $action  accept_all | reject_all | save_preferences | ping | visit | exit
     * @param array|null $preferences ['necessary'=>true, 'analytics'=>bool, 'marketing'=>bool, 'preferences'=>bool]
     */
    public function logConsent(string $action, ?array $preferences = null, ?string $sessionId = null): bool
    {
        $data = ['action' => $action];

        if ($preferences !== null) {
            $data['preferences'] = $preferences;
        }
        if ($sessionId !== null) {
            $data['session_id'] = $sessionId;
        }

        return $this->post("/api/consents/{$this->config->token}/log", $data) !== null;
    }

    // -------------------------------------------------------------------------
    // Ziyaretçi tercihleri
    // GET/POST /api/preferences/{token}
    // -------------------------------------------------------------------------

    public function getPreferences(?string $sessionId = null): ?array
    {
        $qs = $sessionId ? '?session_id=' . urlencode($sessionId) : '';
        return $this->get("/api/preferences/{$this->config->token}{$qs}");
    }

    public function savePreferences(array $preferences, ?string $sessionId = null): ?array
    {
        $data = ['preferences' => $preferences];
        if ($sessionId !== null) {
            $data['session_id'] = $sessionId;
        }
        return $this->post("/api/preferences/{$this->config->token}", $data);
    }

    // -------------------------------------------------------------------------
    // Script tag helper
    // -------------------------------------------------------------------------

    /**
     * Banner script tag'ini döner.
     * Config'teki domain veya scriptUrl'den bundle URL'ini oluşturur.
     * Örn: domain='claude.com' → <script src="https://bundles.veribenim.com/claudecom.js" async></script>
     */
    public function scriptTag(): string
    {
        $url = $this->config->getBundleUrl();
        if (empty($url)) {
            return '<!-- Veribenim: domain veya scriptUrl gerekli -->';
        }
        $url = htmlspecialchars($url, ENT_QUOTES);
        return '<script src="' . $url . '" async></script>';
    }

    // -------------------------------------------------------------------------
    // Form Rızası
    // POST /api/form-consents/{token}
    // -------------------------------------------------------------------------

    /**
     * İletişim formu, üyelik, bülten vb. formlardaki KVKK onayını kaydeder.
     * Şema zorunluluğu yoktur — metadata alanına istediğiniz veriyi ekleyin.
     *
     * @param string $formName   Form adı (örn: 'contact', 'newsletter')
     * @param bool   $consented  Kullanıcının onay verip vermediği
     * @param string $consentText Kullanıcının gördüğü onay metni (opsiyonel)
     * @param array  $metadata   İsteğe bağlı ek veri
     */
    public function logFormConsent(
        string $formName,
        bool $consented,
        string $consentText = '',
        array $metadata = []
    ): ?array {
        $data = [
            'form_name' => $formName,
            'consented' => $consented,
        ];

        if (!empty($consentText)) {
            $data['consent_text'] = $consentText;
        }
        if (!empty($metadata)) {
            $data['metadata'] = $metadata;
        }

        return $this->post("/api/form-consents/{$this->config->token}", $data);
    }

    // -------------------------------------------------------------------------
    // Form Generator — Form şeması ve gönderimi
    // GET  /api/public/forms/{token}/{slug}
    // POST /api/public/forms/{token}/{slug}
    // -------------------------------------------------------------------------

    /**
     * Bir formun şemasını (alanlar, adımlar, ayarlar) getirir.
     *
     * @param string $slug  Form slug'ı (örn: 'iletisim-formu')
     * @param string|null $lang  Dil kodu (tr, en, de, fr, es, bg, ar). null ise environment dili kullanılır.
     * @return array|null   Form şeması veya null (bulunamazsa / pasif)
     */
    public function getFormSchema(string $slug, ?string $lang = null): ?array
    {
        $qs = $lang ? '?lang=' . rawurlencode($lang) : '';
        return $this->get("/api/public/forms/{$this->config->token}/" . rawurlencode($slug) . $qs);
    }

    /**
     * Form verisini Veribenim'e gönderir.
     * Bildirim e-postaları ve webhook'lar otomatik tetiklenir.
     *
     * @param string $slug  Form slug'ı
     * @param array  $data  {field_uuid: değer} şeklinde form verileri
     * @return array|null   Başarı yanıtı veya null
     */
    public function submitForm(string $slug, array $data): ?array
    {
        return $this->post(
            "/api/public/forms/{$this->config->token}/" . rawurlencode($slug),
            $data
        );
    }

    /**
     * Formu sunucu tarafında HTML olarak render eder ve döndürür.
     * Döndürülen HTML'i doğrudan sayfaya basabilirsiniz.
     *
     * Temel form markup'ını üretir; JavaScript ile daha zengin etkileşim
     * için veribenim.js bundle veya JS SDK kullanılması önerilir.
     *
     * @param string $slug     Form slug'ı
     * @param array  $options  ['class' => '...', 'id' => '...', 'action' => '...', 'lang' => 'en']
     * @return string          HTML çıktısı
     */
    public function renderFormHtml(string $slug, array $options = []): string
    {
        $lang = $options['lang'] ?? null;
        $schema = $this->getFormSchema($slug, $lang);

        if (!$schema) {
            return '<!-- Veribenim: form "' . htmlspecialchars($slug) . '" bulunamadı -->';
        }

        $apiUrl   = rtrim($this->config->apiUrl, '/') . "/api/public/forms/{$this->config->token}/" . rawurlencode($slug);
        $formId   = $options['id'] ?? 'vb-form-' . htmlspecialchars($slug);
        $formClass = $options['class'] ?? 'vb-form';
        $fields   = $schema['fields'] ?? [];
        $settings = $schema['settings'] ?? [];
        $submitText = $settings['submit_button_text'] ?? 'Gönder';

        $html  = '<form id="' . $formId . '" class="' . $formClass . '" data-vb-form="' . htmlspecialchars($slug) . '" data-vb-action="' . htmlspecialchars($apiUrl) . '" novalidate>';

        // Alanları sırala
        usort($fields, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        foreach ($fields as $field) {
            $html .= $this->renderField($field);
        }

        $html .= '<button type="submit" class="vb-submit">' . htmlspecialchars($submitText) . '</button>';
        $html .= '<div class="vb-badge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> VeriBenim ile kişisel verileriniz koruma altında</div>';
        $html .= '</form>';

        return $html;
    }

    /**
     * Tek bir form alanını HTML olarak render eder.
     *
     * @internal renderFormHtml tarafından kullanılır.
     */
    private function renderField(array $field): string
    {
        $type     = $field['type'] ?? 'input';
        $uuid     = htmlspecialchars($field['uuid'] ?? '');
        $label    = htmlspecialchars($field['label'] ?? '');
        $placeholder = htmlspecialchars($field['placeholder'] ?? '');
        $required = !empty($field['required']);
        $helpText = htmlspecialchars($field['help_text'] ?? '');
        $reqAttr  = $required ? ' required' : '';
        $reqMark  = $required ? '<span class="vb-required" aria-hidden="true"> *</span>' : '';

        if ($type === 'divider') {
            return '<hr class="vb-divider">';
        }

        if ($type === 'heading') {
            return '<div class="vb-heading">' . $label . '</div>';
        }

        $html = '<div class="vb-field" data-field-uuid="' . $uuid . '">';
        $html .= '<label class="vb-label" for="vb-' . $uuid . '">' . $label . $reqMark . '</label>';

        switch ($type) {
            case 'textarea':
                $rows = (int) ($field['settings']['rows'] ?? 4);
                $html .= '<textarea id="vb-' . $uuid . '" name="' . $uuid . '" class="vb-textarea" placeholder="' . $placeholder . '" rows="' . $rows . '"' . $reqAttr . '></textarea>';
                break;

            case 'dropdown':
                $html .= '<select id="vb-' . $uuid . '" name="' . $uuid . '" class="vb-select"' . $reqAttr . '>';
                $html .= '<option value="">' . ($placeholder ?: 'Seçiniz...') . '</option>';
                foreach ($field['options'] ?? [] as $opt) {
                    $html .= '<option value="' . htmlspecialchars($opt['value']) . '">' . htmlspecialchars($opt['label']) . '</option>';
                }
                $html .= '</select>';
                break;

            case 'radio':
                $html .= '<div class="vb-radio-group">';
                foreach ($field['options'] ?? [] as $opt) {
                    $optVal = htmlspecialchars($opt['value']);
                    $html .= '<label class="vb-radio-item"><input type="radio" name="' . $uuid . '" value="' . $optVal . '"' . $reqAttr . '> ' . htmlspecialchars($opt['label']) . '</label>';
                }
                $html .= '</div>';
                break;

            case 'checkbox':
                $html .= '<div class="vb-checkbox-group">';
                foreach ($field['options'] ?? [] as $opt) {
                    $optVal = htmlspecialchars($opt['value']);
                    $html .= '<label class="vb-checkbox-item"><input type="checkbox" name="' . $uuid . '[]" value="' . $optVal . '"> ' . htmlspecialchars($opt['label']) . '</label>';
                }
                $html .= '</div>';
                break;

            case 'file':
                $accept = implode(',', $field['validation']['file_types'] ?? []);
                $multi  = !empty($field['settings']['multiple']) ? ' multiple' : '';
                $html  .= '<input type="file" id="vb-' . $uuid . '" name="' . $uuid . '" class="vb-input"' . ($accept ? ' accept="' . $accept . '"' : '') . $multi . $reqAttr . '>';
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

            default: // input
                $html .= '<input type="text" id="vb-' . $uuid . '" name="' . $uuid . '" class="vb-input" placeholder="' . $placeholder . '"' . $reqAttr . '>';
        }

        if ($helpText) {
            $html .= '<div class="vb-help">' . $helpText . '</div>';
        }

        $html .= '<div class="vb-error" data-error-for="' . $uuid . '" hidden></div>';
        $html .= '</div>';

        return $html;
    }

    // -------------------------------------------------------------------------
    // DSAR (Veri Sahibi Başvurusu)
    // POST /api/dsar/{token}
    // -------------------------------------------------------------------------

    /**
     * Ziyaretçinin veri hakkı başvurusunu oluşturur.
     * 30 günlük yasal süre otomatik hesaplanır.
     *
     * @param string $requestType  access|rectification|erasure|restriction|portability|objection|automated
     * @param string $fullName     Başvuranın adı soyadı
     * @param string $email        İletişim e-posta adresi
     * @param string $description  Başvuru açıklaması (opsiyonel)
     */
    public function submitDsar(
        string $requestType,
        string $fullName,
        string $email,
        string $description = ''
    ): ?array {
        $validTypes = ['access', 'rectification', 'erasure', 'restriction', 'portability', 'objection', 'automated'];
        if (!in_array($requestType, $validTypes, true)) {
            throw new \InvalidArgumentException("[Veribenim] Geçersiz DSAR tipi: {$requestType}");
        }

        $data = [
            'request_type' => $requestType,
            'full_name'    => $fullName,
            'email'        => $email,
        ];

        if (!empty($description)) {
            $data['description'] = $description;
        }

        return $this->post("/api/dsar/{$this->config->token}", $data);
    }

    // -------------------------------------------------------------------------
    // HTTP helpers (curl — ext-curl opsiyonel, fallback: file_get_contents)
    // -------------------------------------------------------------------------

    private function get(string $path): ?array
    {
        return $this->request('GET', $path);
    }

    private function post(string $path, array $body): ?array
    {
        return $this->request('POST', $path, $body);
    }

    private function request(string $method, string $path, ?array $body = null): ?array
    {
        $url = rtrim($this->config->apiUrl, '/') . $path;

        if ($this->config->debug) {
            error_log("[Veribenim] {$method} {$url}");
        }

        if (extension_loaded('curl')) {
            return $this->curlRequest($method, $url, $body);
        }

        return $this->streamRequest($method, $url, $body);
    }

    private function curlRequest(string $method, string $url, ?array $body): ?array
    {
        $ch = curl_init($url);
        $headers = ['Accept: application/json', 'Content-Type: application/json'];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->config->timeout,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            return null;
        }

        return json_decode($response, true) ?: null;
    }

    private function streamRequest(string $method, string $url, ?array $body): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method'  => $method,
                'header'  => "Content-Type: application/json\r\nAccept: application/json",
                'content' => $body ? json_encode($body) : null,
                'timeout' => $this->config->timeout,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }

        return json_decode($response, true) ?: null;
    }
}
