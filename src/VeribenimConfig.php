<?php

declare(strict_types=1);

namespace Veribenim;

class VeribenimConfig
{
    public function __construct(
        /** Environment token — Veribenim panelinden alınır (32 karakter) */
        public readonly string $token,
        /** Site domain'i — Bundle URL'ini oluşturmak için. Örn: 'claude.com' */
        public readonly string $domain = '',
        /** Banner dili. Varsayılan: 'tr' */
        public readonly string $lang = 'tr',
        /** HTTP timeout saniye. Varsayılan: 5 */
        public readonly int $timeout = 5,
        /** Debug modu. Varsayılan: false */
        public readonly bool $debug = false,
        /** @internal — Yalnızca ileri düzey kullanım */
        public readonly string $apiUrl = 'https://live.veribenim.com',
        /** @internal — Tam script URL override'ı. Belirtilmezse domain'den türetilir. */
        public readonly string $scriptUrl = '',
    ) {
        if (empty($token)) {
            throw new \InvalidArgumentException('[Veribenim] token boş olamaz');
        }
    }

    /**
     * Domain'den bundle dosya adını türetir.
     * Backend CookieBundleService::cleanDomainForFilename() ile aynı mantık.
     */
    public static function cleanDomainForFilename(string $url): string
    {
        $domain = preg_replace('(^https?://)', '', $url);
        $domain = preg_replace('/^www\./', '', $domain);
        $domain = preg_replace('/[^a-z0-9]/', '', strtolower($domain));
        return $domain ?: 'bundle';
    }

    /**
     * Bundle script URL'ini döner.
     * Öncelik: scriptUrl > domain > boş
     */
    public function getBundleUrl(): string
    {
        if (!empty($this->scriptUrl)) {
            return $this->scriptUrl;
        }
        if (!empty($this->domain)) {
            $filename = self::cleanDomainForFilename($this->domain);
            return "https://bundles.veribenim.com/{$filename}.js";
        }
        return '';
    }
}
