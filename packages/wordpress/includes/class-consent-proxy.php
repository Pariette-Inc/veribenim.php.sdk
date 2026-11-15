<?php

declare(strict_types=1);

if (! defined('ABSPATH')) exit;

/**
 * Birinci taraf rıza proxy'si — WordPress adaptörü.
 *
 * Bundle, doğrulanmış proxy kurulu sitede kararı aynı origin'e gönderir:
 *   POST https://site.com/_vb/{token}/c        → {api}/api/vp/{token}/c
 *   GET  https://site.com/_vb/{token}/ping?n=… → {api}/api/vp/{token}/ping (konsoldaki "Doğrula")
 * Yanıttaki Set-Cookie (vb_consent_xxxxxxxx) sitenin kendi çerezi olur ve
 * Safari ITP'nin 7 gün sınırına takılmaz. Yol sabittir (/_vb/), bu yüzden
 * REST API (/wp-json/…) yerine istek `init`'te erken yakalanır.
 *
 * Kurallar (PHP çekirdeğindeki Veribenim\Proxy\ConsentProxy ile aynı; eklenti
 * WordPress.org'a tek başına dağıtıldığı için burada bağımsız uygulanır):
 *  - Yalnızca POST /_vb/{site token}/c ve GET /_vb/{site token}/ping; başka her şey 404.
 *  - İstek başlıkları izin listesiyle kurulur; Cookie yalnızca bu sitenin rıza
 *    çerezine indirilir (WordPress oturum çerezleri asla gitmez).
 *  - Anahtar (VERIBENIM_PROXY_KEY sabiti ya da veribenim_proxy_key seçeneği)
 *    yalnızca upstream isteğine eklenir; yanıtta görünmez.
 *  - Ziyaretçi IP'si REMOTE_ADDR'dir; CDN arkasında `veribenim_proxy_client_ip`
 *    filtresiyle güvenilir kaynaktan verilmelidir (X-Forwarded-For'u körü
 *    körüne okumayın).
 *  - Yanıttan yalnızca Content-Type, Cache-Control ve rıza çerezinin Set-Cookie'si
 *    taşınır; yönlendirme takip edilmez.
 */
class Veribenim_Consent_Proxy
{
    public const PATH_PREFIX = '/_vb';
    public const MAX_BODY_BYTES = 65536;

    private const FORWARD_REQUEST_HEADERS = ['content-type', 'accept', 'accept-language', 'user-agent', 'origin', 'referer'];
    private const FORWARD_RESPONSE_HEADERS = ['content-type', 'cache-control'];

    /** @var callable(array):void yanıtı yazıp isteği bitirir (testte değiştirilir) */
    private $emitter;

    public function __construct(?callable $emitter = null)
    {
        $this->emitter = $emitter ?? [self::class, 'emit_and_exit'];
    }

    public static function cookie_name(string $token): string
    {
        return 'vb_consent_' . substr(hash('sha256', 'vb-consent-cookie|' . $token), 0, 8);
    }

    public static function proxy_key(): string
    {
        if (defined('VERIBENIM_PROXY_KEY')) {
            return (string) constant('VERIBENIM_PROXY_KEY');
        }
        return (string) get_option('veribenim_proxy_key', '');
    }

    public static function is_enabled(): bool
    {
        return (bool) get_option('veribenim_proxy_enabled', false)
            && self::proxy_key() !== ''
            && (string) get_option('veribenim_token', '') !== '';
    }

    /**
     * `init` (öncelik 0) kancası: istek /_vb/ altındaysa işler ve sonlandırır.
     */
    public function maybe_handle(): void
    {
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput -- ham istek, aşağıda doğrulanıyor
        $uri  = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = (string) wp_parse_url($uri, PHP_URL_PATH);

        if ($path !== self::PATH_PREFIX && strpos($path, self::PATH_PREFIX . '/') !== 0) {
            return;
        }
        if (! self::is_enabled()) {
            return; // kapalıyken WordPress'in kendi 404'ü
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (is_string($value) && strpos((string) $key, 'HTTP_') === 0) {
                $headers[strtolower(str_replace('_', '-', substr((string) $key, 5)))] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
        $remote = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        // phpcs:enable

        $client_ip = (string) apply_filters('veribenim_proxy_client_ip', $remote);
        $body = (string) file_get_contents('php://input', false, null, 0, self::MAX_BODY_BYTES + 1);

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- herkese açık uç; nonce yalnızca biçim denetiminden geçerse iletilir
        $nonce = isset($_GET['n']) ? (string) wp_unslash($_GET['n']) : '';

        ($this->emitter)($this->handle($method, $path, $headers, $body, $client_ip, $nonce));
    }

    /**
     * @param array<string,string> $headers küçük harfli başlıklar
     * @return array{status:int, headers: array<int, array{0:string,1:string}>, body:string}
     */
    public function handle(string $method, string $path, array $headers, string $body, string $client_ip, string $nonce = ''): array
    {
        $token = (string) get_option('veribenim_token', '');
        $key   = self::proxy_key();

        if (! preg_match('/^[A-Za-z0-9]{16,64}$/', $token) || $key === '') {
            return self::error(404, 'VB_PROXY_NOT_FOUND');
        }

        $endpoint = null;
        if ($path === self::PATH_PREFIX . '/' . $token . '/c') {
            $endpoint = 'c';
        } elseif ($path === self::PATH_PREFIX . '/' . $token . '/ping') {
            $endpoint = 'ping';
        }
        if ($endpoint === null) {
            return self::error(404, 'VB_PROXY_NOT_FOUND');
        }

        $allowed = $endpoint === 'c' ? 'POST' : 'GET';
        if (strtoupper($method) !== $allowed) {
            $res = self::error(405, 'VB_PROXY_METHOD_NOT_ALLOWED');
            $res['headers'][] = ['Allow', $allowed];
            return $res;
        }

        if ($endpoint === 'ping') {
            $body = '';
        }
        if (strlen($body) > self::MAX_BODY_BYTES) {
            return self::error(413, 'VB_PROXY_PAYLOAD_TOO_LARGE');
        }

        $url = Veribenim_Frontend::api_url() . '/api/vp/' . $token . '/' . $endpoint;
        if ($endpoint === 'ping' && preg_match('/^[A-Za-z0-9]{16,64}$/', $nonce)) {
            $url .= '?n=' . $nonce;
        }

        $cookie_name = self::cookie_name($token);
        $out_headers = [];
        $headers = array_change_key_case($headers, CASE_LOWER);
        foreach (self::FORWARD_REQUEST_HEADERS as $name) {
            if (isset($headers[$name]) && $headers[$name] !== '' && ! preg_match('/[\r\n]/', (string) $headers[$name])) {
                $out_headers[$name] = (string) $headers[$name];
            }
        }
        $cookie = self::filter_cookie((string) ($headers['cookie'] ?? ''), $cookie_name);
        if ($cookie !== '') {
            $out_headers['cookie'] = $cookie;
        }
        $out_headers['X-VB-Proxy-Key'] = $key;
        if ($client_ip !== '' && filter_var($client_ip, FILTER_VALIDATE_IP)) {
            $out_headers['X-VB-Client-IP'] = $client_ip;
        }

        $response = wp_remote_request($url, [
            'method'      => $allowed,
            'headers'     => $out_headers,
            'body'        => $endpoint === 'c' ? $body : null,
            'timeout'     => 5,
            'redirection' => 0,
            'sslverify'   => true,
            'cookies'     => [],
        ]);

        if (is_wp_error($response)) {
            return self::error(502, 'VB_PROXY_UPSTREAM_ERROR');
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 600 || ($status >= 300 && $status < 400)) {
            return self::error(502, 'VB_PROXY_UPSTREAM_ERROR');
        }

        $result_headers = [];
        $has_cache_control = false;
        foreach (self::header_pairs(wp_remote_retrieve_headers($response)) as [$name, $value]) {
            $lower = strtolower($name);
            if (preg_match('/[\r\n]/', $value) || strpos($value, $key) !== false) {
                continue;
            }
            if ($lower === 'set-cookie') {
                if (trim(explode('=', $value, 2)[0]) === $cookie_name) {
                    $result_headers[] = ['Set-Cookie', $value];
                }
                continue;
            }
            if (in_array($lower, self::FORWARD_RESPONSE_HEADERS, true)) {
                $result_headers[] = [$name, $value];
                $has_cache_control = $has_cache_control || $lower === 'cache-control';
            }
        }
        if (! $has_cache_control) {
            $result_headers[] = ['Cache-Control', 'no-store'];
        }

        $res_body = (string) wp_remote_retrieve_body($response);
        if (strpos($res_body, $key) !== false) {
            $res_body = str_replace($key, '[redacted]', $res_body);
        }

        return ['status' => $status, 'headers' => $result_headers, 'body' => $res_body];
    }

    private static function filter_cookie(string $raw, string $cookie_name): string
    {
        foreach (explode(';', $raw) as $part) {
            $pair = trim($part);
            if ($pair === '' || strpos($pair, '=') === false) {
                continue;
            }
            [$name, $value] = explode('=', $pair, 2);
            if (trim($name) === $cookie_name && preg_match('/^[A-Za-z0-9_\-=.]{0,4096}$/', $value)) {
                return $cookie_name . '=' . $value;
            }
        }
        return '';
    }

    /**
     * wp_remote_retrieve_headers() çıktısını [ad, değer] listesine çevirir.
     * Aynı addan birden çok başlık (Set-Cookie) WordPress'te dizi olarak gelir.
     *
     * @param mixed $headers
     * @return array<int, array{0:string,1:string}>
     */
    private static function header_pairs($headers): array
    {
        if (is_object($headers) && method_exists($headers, 'getAll')) {
            $headers = $headers->getAll();
        }
        $pairs = [];
        if (! is_iterable($headers)) {
            return $pairs;
        }
        foreach ($headers as $name => $value) {
            foreach ((array) $value as $v) {
                $pairs[] = [(string) $name, (string) $v];
            }
        }
        return $pairs;
    }

    /** @return array{status:int, headers: array<int, array{0:string,1:string}>, body:string} */
    private static function error(int $status, string $code): array
    {
        return [
            'status'  => $status,
            'headers' => [['Content-Type', 'application/json'], ['Cache-Control', 'no-store']],
            'body'    => (string) wp_json_encode(['status' => false, 'code' => $code]),
        ];
    }

    /**
     * @param array{status:int, headers: array<int, array{0:string,1:string}>, body:string} $result
     */
    public static function emit_and_exit(array $result): void
    {
        if (! headers_sent()) {
            status_header($result['status']);
            foreach ($result['headers'] as [$name, $value]) {
                header($name . ': ' . $value, false); // Set-Cookie aynen
            }
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- upstream JSON gövdesi, HTML değil
        echo $result['body'];
        exit;
    }
}
