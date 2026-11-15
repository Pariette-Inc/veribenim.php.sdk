<?php

declare(strict_types=1);

namespace Veribenim\Proxy;

/**
 * Proxy yanıtı. Başlıklar liste olarak tutulur: birden çok `Set-Cookie`
 * aynı adla yan yana taşınabilsin.
 */
final class ConsentProxyResponse
{
    /**
     * @param int                                    $status
     * @param array<int, array{0: string, 1: string}> $headers [ad, değer] çiftleri
     * @param string                                 $body
     */
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body,
    ) {
    }

    /** @return array<int, string> */
    public function headerValues(string $name): array
    {
        $out = [];
        foreach ($this->headers as [$n, $v]) {
            if (strcasecmp($n, $name) === 0) {
                $out[] = $v;
            }
        }
        return $out;
    }

    public static function json(int $status, array $payload, array $extraHeaders = []): self
    {
        return new self(
            $status,
            array_merge([
                ['Content-Type', 'application/json'],
                ['Cache-Control', 'no-store'],
            ], $extraHeaders),
            (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }

    /**
     * Yanıtı doğrudan PHP SAPI'ye yazar (çerçevesiz kullanım). Set-Cookie
     * değerleri olduğu gibi (yeniden biçimlendirmeden) gönderilir.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as [$name, $value]) {
                header($name . ': ' . $value, false);
            }
        }
        echo $this->body;
    }
}
