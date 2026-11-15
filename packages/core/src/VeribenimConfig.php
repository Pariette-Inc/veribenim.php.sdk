<?php

declare(strict_types=1);

namespace Veribenim;

class VeribenimConfig
{
    /**
     * Varsayılan adresler — bugünkü bulut kurulumu. Kurum içi (On-Prem)
     * kurulumda kurucu parametreleriyle değiştirilir; tek kaynak burasıdır.
     */
    public const DEFAULT_API_URL = 'https://live.veribenim.com';
    public const DEFAULT_BUNDLE_BASE_URL = 'https://bundles.veribenim.com';
    public const DEFAULT_WEB_URL = 'https://veribenim.com';

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
        /** API tabanı. Varsayılan: https://live.veribenim.com (On-Prem'de kendi adresiniz) */
        public readonly string $apiUrl = self::DEFAULT_API_URL,
        /** @internal — Tam script URL override'ı. Belirtilmezse domain'den türetilir. */
        public readonly string $scriptUrl = '',
        /** Bundle dosyalarının tabanı. Varsayılan: https://bundles.veribenim.com */
        public readonly string $bundleBaseUrl = self::DEFAULT_BUNDLE_BASE_URL,
        /** Panel / web sitesi adresi. Varsayılan: https://veribenim.com */
        public readonly string $webUrl = self::DEFAULT_WEB_URL,
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
            $base = rtrim($this->bundleBaseUrl !== '' ? $this->bundleBaseUrl : self::DEFAULT_BUNDLE_BASE_URL, '/');
            return "{$base}/{$filename}.js";
        }
        return '';
    }
}
