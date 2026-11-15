<?php

declare(strict_types=1);

namespace Veribenim\Proxy;

/**
 * Çerçeveden bağımsız proxy isteği. Laravel / WordPress / düz PHP adaptörleri
 * kendi istek nesnelerini buna çevirir.
 *
 * `clientIp` adaptörün GÜVENİLİR kaynaktan çözdüğü ziyaretçi IP'sidir
 * (Laravel: TrustProxies'e uyan $request->ip(); düz PHP: ClientIp::resolve()).
 * İstemcinin gönderdiği X-Forwarded-For burada asla doğrudan kullanılmaz.
 */
final class ConsentProxyRequest
{
    /** @var array<string, string> küçük harfli başlık adı => değer */
    public readonly array $headers;

    /**
     * @param string               $method   HTTP yöntemi (GET/POST…)
     * @param string               $path     İstek yolu, sorgu dizesi HARİÇ (örn. "/_vb/{token}/c")
     * @param array<string, mixed> $query    Sorgu parametreleri
     * @param array<string, mixed> $headers  Başlıklar (ad => değer ya da değer listesi)
     * @param string               $body     Ham gövde
     * @param string|null          $clientIp Güvenilir kaynaktan çözülmüş ziyaretçi IP'si
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        array $headers = [],
        public readonly string $body = '',
        public readonly ?string $clientIp = null,
    ) {
        $normalized = [];
        foreach ($headers as $name => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            }
            $normalized[strtolower((string) $name)] = (string) $value;
        }
        $this->headers = $normalized;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * PHP süper global'lerinden istek kurar (çerçevesiz kullanım).
     *
     * @param array<int, string> $trustedProxies  REMOTE_ADDR bu listedeyse (IP ya da CIDR)
     *                                            $clientIpHeader başlığına güvenilir.
     * @param string             $clientIpHeader  Örn. 'X-Forwarded-For', 'CF-Connecting-IP'
     */
    public static function fromGlobals(array $trustedProxies = [], string $clientIpHeader = 'X-Forwarded-For'): self
    {
        $server = $_SERVER;
        $headers = [];
        foreach ($server as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            } elseif ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $headers[str_replace('_', '-', $key)] = $value;
            }
        }

        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?? '/');

        // En fazla limit + 1 bayt okunur; fazlası ConsentProxy'de 413 olur.
        $body = (string) file_get_contents('php://input', false, null, 0, ConsentProxy::MAX_BODY_BYTES + 1);

        return new self(
            method: strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET')),
            path: $path,
            query: $_GET,
            headers: $headers,
            body: $body,
            clientIp: ClientIp::resolve($server, $trustedProxies, $clientIpHeader),
        );
    }
}
