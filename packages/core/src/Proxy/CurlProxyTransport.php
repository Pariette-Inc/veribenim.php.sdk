<?php

declare(strict_types=1);

namespace Veribenim\Proxy;

/**
 * ext-curl ile upstream isteği. Yönlendirme takip edilmez, TLS doğrulanır,
 * yanıt başlıkları ham hâliyle (Set-Cookie dahil, her biri ayrı) toplanır.
 */
final class CurlProxyTransport implements ProxyTransport
{
    public function send(string $method, string $url, array $headers, string $body, int $timeout): array
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('ext-curl gerekli');
        }

        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }
        // cURL'ün kendiliğinden eklediği "Expect: 100-continue"yu kapat.
        $lines[] = 'Expect:';

        $responseHeaders = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $lines,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS | CURLPROTO_HTTP,
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$responseHeaders): int {
                $trimmed = trim($line);
                if (str_starts_with($trimmed, 'HTTP/')) {
                    $responseHeaders = []; // ara yanıt (100-continue) başlıklarını at
                } elseif ($trimmed !== '' && str_contains($trimmed, ':')) {
                    [$name, $value] = explode(':', $trimmed, 2);
                    $responseHeaders[] = [trim($name), trim($value)];
                }
                return strlen($line);
            },
        ]);
        if ($method !== 'GET' && $method !== 'HEAD') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            throw new \RuntimeException('upstream isteği başarısız: ' . $error);
        }

        return ['status' => $status, 'headers' => $responseHeaders, 'body' => (string) $result];
    }
}
