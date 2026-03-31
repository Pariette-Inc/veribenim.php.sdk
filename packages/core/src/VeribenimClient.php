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
     * Bundle URL'si Veribenim panelinden alınıp $scriptUrl olarak verilmelidir.
     * Panelde: Siteniz → Entegrasyon → Bundle URL
     */
    public function scriptTag(): string
    {
        $url = htmlspecialchars($this->config->scriptUrl, ENT_QUOTES);
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
