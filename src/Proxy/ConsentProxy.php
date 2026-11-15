<?php

declare(strict_types=1);

namespace Veribenim\Proxy;

use Veribenim\VeribenimConfig;

/**
 * Birinci taraf rıza proxy'si — çerçeveden bağımsız işleyici.
 *
 * Müşterinin kendi alan adındaki `/_vb/{token}/*` isteklerini Veribenim'in
 * `{apiUrl}/api/vp/{token}/*` uçlarına iletir; yanıttaki rıza çerezini
 * (`Set-Cookie: vb_consent_xxxxxxxx=…`) aynen geri taşır. Böylece çerez
 * sitenin KENDİ origin'inden HTTP ile yazılır ve Safari ITP'nin 7 gün sınırına
 * takılmaz. Ayrıntı: API deposu docs/FIRST_PARTY_CONSENT_COOKIE.md.
 *
 * Güvenlik kuralları (hepsi bu sınıfta zorlanır):
 *  - Yalnızca iki yol iletilir: POST {prefix}/{token}/c ve GET {prefix}/{token}/ping.
 *    Token yapılandırmadaki site token'ıyla BİREBİR eşleşmeli; başka her şey 404
 *    (açık proxy değildir, yol/sorgu birleştirme yoktur).
 *  - İstek başlıkları izin listesiyle sıfırdan kurulur. Cookie başlığı yalnızca
 *    bu sitenin rıza çerezine indirilir; Authorization, X-Forwarded-For,
 *    X-Real-IP ve sitenin oturum çerezleri asla iletilmez.
 *  - `X-VB-Proxy-Key` yalnızca upstream isteğine eklenir; yanıta yazılmaz,
 *    gövdede geçerse maskelenir. Hata yanıtları ayrıntı içermez.
 *  - `X-VB-Client-IP` adaptörün güvenilir kaynaktan çözdüğü IP'dir
 *    (ConsentProxyRequest::$clientIp); istemci başlığı doğrudan kullanılmaz.
 *  - Yanıttan yalnızca Content-Type, Cache-Control ve bu sitenin rıza
 *    çerezine ait Set-Cookie taşınır. Yönlendirmeler takip edilmez (502).
 *  - Gövde 64 KB ile sınırlı (413).
 */
final class ConsentProxy
{
    public const PATH_PREFIX = '/_vb';
    public const MAX_BODY_BYTES = 65536;
    public const HEADER_KEY = 'X-VB-Proxy-Key';
    public const HEADER_CLIENT_IP = 'X-VB-Client-IP';

    /** Upstream'e iletilen istemci başlıkları (küçük harf). */
    private const FORWARD_REQUEST_HEADERS = [
        'content-type',
        'accept',
        'accept-language',
        'user-agent',
        // origin.check proxy rotasında da çalışır: tarayıcının Origin/Referer'ı gerekir.
        'origin',
        'referer',
    ];

    /** İstemciye geri taşınan yanıt başlıkları (Set-Cookie ayrıca süzülür). */
    private const FORWARD_RESPONSE_HEADERS = ['content-type', 'cache-control'];

    private readonly string $upstreamBase;
    private readonly string $prefix;
    private readonly string $cookieName;
    private readonly ProxyTransport $transport;

    public function __construct(
        private readonly string $token,
        #[\SensitiveParameter]
        private readonly string $proxyKey,
        string $apiUrl = VeribenimConfig::DEFAULT_API_URL,
        ?ProxyTransport $transport = null,
        private readonly int $timeout = 5,
        string $pathPrefix = self::PATH_PREFIX,
    ) {
        if (!preg_match('/^[A-Za-z0-9]{16,64}$/', $token)) {
            throw new \InvalidArgumentException('[Veribenim] ConsentProxy: geçersiz site token');
        }
        if (trim($proxyKey) === '') {
            throw new \InvalidArgumentException('[Veribenim] ConsentProxy: proxy anahtarı boş olamaz');
        }
        $apiUrl = rtrim(trim($apiUrl), '/');
        $scheme = strtolower((string) parse_url($apiUrl, PHP_URL_SCHEME));
        if (!in_array($scheme, ['https', 'http'], true) || !parse_url($apiUrl, PHP_URL_HOST)) {
            throw new \InvalidArgumentException('[Veribenim] ConsentProxy: apiUrl http(s) adresi olmalı');
        }

        $this->upstreamBase = $apiUrl . '/api/vp/' . $token;
        $this->prefix = '/' . trim($pathPrefix, '/');
        $this->cookieName = self::cookieName($token);
        $this->transport = $transport ?? new CurlProxyTransport();
    }

    public static function fromConfig(
        VeribenimConfig $config,
        #[\SensitiveParameter] string $proxyKey,
        ?ProxyTransport $transport = null,
    ): self {
        return new self($config->token, $proxyKey, $config->apiUrl, $transport, $config->timeout);
    }

    /**
     * Sitenin rıza çerezinin adı — API'deki ConsentCookie::name() ile aynı:
     * "vb_consent_" + sha256("vb-consent-cookie|" + token)'ın ilk 8 hanesi.
     */
    public static function cookieName(string $token): string
    {
        return 'vb_consent_' . substr(hash('sha256', 'vb-consent-cookie|' . $token), 0, 8);
    }

    /** Bu yol proxy önekinin altında mı? (Adaptörlerin erken eleme kontrolü.) */
    public function handles(string $path): bool
    {
        return $path === $this->prefix || str_starts_with($path, $this->prefix . '/');
    }

    public function handle(ConsentProxyRequest $request): ConsentProxyResponse
    {
        $endpoint = match ($request->path) {
            $this->prefix . '/' . $this->token . '/c'    => 'c',
            $this->prefix . '/' . $this->token . '/ping' => 'ping',
            default                                     => null,
        };

        if ($endpoint === null) {
            return ConsentProxyResponse::json(404, ['status' => false, 'code' => 'VB_PROXY_NOT_FOUND']);
        }

        $method = strtoupper($request->method);
        $allowed = $endpoint === 'c' ? 'POST' : 'GET';
        if ($method !== $allowed) {
            return ConsentProxyResponse::json(
                405,
                ['status' => false, 'code' => 'VB_PROXY_METHOD_NOT_ALLOWED'],
                [['Allow', $allowed]],
            );
        }

        $body = $endpoint === 'c' ? $request->body : '';
        if (strlen($body) > self::MAX_BODY_BYTES) {
            return ConsentProxyResponse::json(413, ['status' => false, 'code' => 'VB_PROXY_PAYLOAD_TOO_LARGE']);
        }

        $url = $this->upstreamBase . '/' . $endpoint;
        if ($endpoint === 'ping') {
            $nonce = $request->query['n'] ?? null;
            if (is_string($nonce) && preg_match('/^[A-Za-z0-9]{16,64}$/', $nonce)) {
                $url .= '?n=' . $nonce;
            }
        }

        try {
            $upstream = $this->transport->send($method, $url, $this->upstreamHeaders($request), $body, $this->timeout);
        } catch (\Throwable) {
            // Ayrıntı (adres, anahtar, hata metni) yanıta yazılmaz.
            return ConsentProxyResponse::json(502, ['status' => false, 'code' => 'VB_PROXY_UPSTREAM_ERROR']);
        }

        $status = (int) ($upstream['status'] ?? 0);
        if ($status < 200 || $status >= 600 || ($status >= 300 && $status < 400)) {
            // Yönlendirme takip edilmez ve istemciye de taşınmaz.
            return ConsentProxyResponse::json(502, ['status' => false, 'code' => 'VB_PROXY_UPSTREAM_ERROR']);
        }

        return new ConsentProxyResponse(
            $status,
            $this->responseHeaders($upstream['headers'] ?? []),
            $this->redact((string) ($upstream['body'] ?? '')),
        );
    }

    /** @return array<string, string> */
    private function upstreamHeaders(ConsentProxyRequest $request): array
    {
        $headers = [];
        foreach (self::FORWARD_REQUEST_HEADERS as $name) {
            $value = $request->header($name);
            if ($value !== null && $value !== '' && !preg_match('/[\r\n]/', $value)) {
                $headers[$name] = $value;
            }
        }

        $cookie = $this->filterCookieHeader((string) $request->header('cookie'));
        if ($cookie !== '') {
            $headers['cookie'] = $cookie;
        }

        $headers[self::HEADER_KEY] = $this->proxyKey;

        if (ClientIp::isValid($request->clientIp)) {
            $headers[self::HEADER_CLIENT_IP] = (string) $request->clientIp;
        }

        return $headers;
    }

    /** Cookie başlığını yalnızca bu sitenin rıza çerezine indirir. */
    private function filterCookieHeader(string $raw): string
    {
        foreach (explode(';', $raw) as $part) {
            $pair = trim($part);
            if ($pair === '' || !str_contains($pair, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $pair, 2);
            if (trim($name) === $this->cookieName && preg_match('/^[A-Za-z0-9_\-=.]{0,4096}$/', $value)) {
                return $this->cookieName . '=' . $value;
            }
        }
        return '';
    }

    /**
     * @param array<int, array{0:string, 1:string}> $headers
     * @return array<int, array{0:string, 1:string}>
     */
    private function responseHeaders(array $headers): array
    {
        $out = [];
        $hasCacheControl = false;
        foreach ($headers as $pair) {
            if (!is_array($pair) || count($pair) < 2) {
                continue;
            }
            [$name, $value] = [(string) $pair[0], (string) $pair[1]];
            $lower = strtolower($name);
            if (preg_match('/[\r\n]/', $value) || str_contains($value, $this->proxyKey)) {
                continue;
            }

            if ($lower === 'set-cookie') {
                $cookieName = trim(explode('=', $value, 2)[0]);
                if ($cookieName === $this->cookieName) {
                    $out[] = ['Set-Cookie', $value]; // aynen
                }
                continue;
            }

            if (in_array($lower, self::FORWARD_RESPONSE_HEADERS, true)) {
                $out[] = [$name, $value];
                $hasCacheControl = $hasCacheControl || $lower === 'cache-control';
            }
        }

        if (!$hasCacheControl) {
            $out[] = ['Cache-Control', 'no-store'];
        }

        return $out;
    }

    private function redact(string $body): string
    {
        return str_contains($body, $this->proxyKey)
            ? str_replace($this->proxyKey, '[redacted]', $body)
            : $body;
    }
}
