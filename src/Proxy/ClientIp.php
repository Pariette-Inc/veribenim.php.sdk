<?php

declare(strict_types=1);

namespace Veribenim\Proxy;

/**
 * Ziyaretçi IP'sini güvenilir kaynaktan çözer.
 *
 * Kural: varsayılan olarak yalnızca REMOTE_ADDR (TCP eşi) kullanılır.
 * Bir CDN / yük dengeleyici arkasındaysanız onun adreslerini
 * $trustedProxies'e verin; ancak o zaman ve yalnızca istek o adreslerden
 * gelmişse $header (örn. X-Forwarded-For) okunur. X-Forwarded-For sağdan
 * sola yürünür ve güvenilir olmayan ilk adres alınır — sol uçtaki değerler
 * istemci tarafından uydurulabilir.
 *
 * Bu IP API'ye `X-VB-Client-IP` olarak gider ve KVKK rıza kaydına yazılır;
 * yanlış/uydurma IP kanıt değerini bozar.
 */
final class ClientIp
{
    /**
     * @param array<string, mixed> $server         $_SERVER benzeri dizi
     * @param array<int, string>   $trustedProxies IP ya da CIDR listesi
     */
    public static function resolve(array $server, array $trustedProxies = [], string $header = 'X-Forwarded-For'): ?string
    {
        $remote = trim((string) ($server['REMOTE_ADDR'] ?? ''));
        if (!self::isValid($remote)) {
            return null;
        }

        if ($trustedProxies === [] || !self::inList($remote, $trustedProxies)) {
            return $remote;
        }

        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        $raw = trim((string) ($server[$key] ?? ''));
        if ($raw === '') {
            return $remote;
        }

        $candidates = array_map('trim', explode(',', $raw));
        for ($i = count($candidates) - 1; $i >= 0; $i--) {
            $ip = $candidates[$i];
            if (!self::isValid($ip)) {
                // Bozuk kayıt: zincire artık güvenilemez, TCP eşine dön.
                return $remote;
            }
            if (!self::inList($ip, $trustedProxies)) {
                return $ip;
            }
        }

        return $remote;
    }

    public static function isValid(?string $ip): bool
    {
        return is_string($ip) && $ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * @param array<int, string> $list
     */
    public static function inList(string $ip, array $list): bool
    {
        foreach ($list as $entry) {
            if (self::matches($ip, trim((string) $entry))) {
                return true;
            }
        }
        return false;
    }

    private static function matches(string $ip, string $entry): bool
    {
        if ($entry === '') {
            return false;
        }
        if (!str_contains($entry, '/')) {
            return $ip === $entry;
        }

        [$subnet, $bits] = explode('/', $entry, 2);
        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin) || !ctype_digit($bits)) {
            return false;
        }

        $bits = (int) $bits;
        $maxBits = strlen($ipBin) * 8;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        if (substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
            return false;
        }
        $rest = $bits % 8;
        if ($rest === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $rest)) & 0xFF;

        return (ord($ipBin[$fullBytes]) & $mask) === (ord($subnetBin[$fullBytes]) & $mask);
    }
}
