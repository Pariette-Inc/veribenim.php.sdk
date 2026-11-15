<?php

declare(strict_types=1);

namespace Veribenim\Proxy;

/**
 * Upstream'e (Veribenim API) tek bir HTTP isteği gönderen katman.
 * Uygulamalar yönlendirme TAKİP ETMEMELİ ve TLS doğrulamasını açık tutmalıdır.
 */
interface ProxyTransport
{
    /**
     * @param array<string, string> $headers
     * @return array{status:int, headers: array<int, array{0:string, 1:string}>, body:string}
     *
     * @throws \RuntimeException ağ hatası / zaman aşımı
     */
    public function send(string $method, string $url, array $headers, string $body, int $timeout): array;
}
