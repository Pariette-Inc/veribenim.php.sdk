<?php

declare(strict_types=1);

namespace Veribenim;

class VeribenimConfig
{
    public function __construct(
        /** Environment token — Veribenim panelinden alınır (32 karakter) */
        public readonly string $token,
        /** Banner dili. Varsayılan: 'tr' */
        public readonly string $lang = 'tr',
        /** HTTP timeout saniye. Varsayılan: 5 */
        public readonly int $timeout = 5,
        /** Debug modu. Varsayılan: false */
        public readonly bool $debug = false,
        /** @internal — Yalnızca ileri düzey kullanım */
        public readonly string $apiUrl = 'https://live.veribenim.com',
        /** @internal — Yalnızca ileri düzey kullanım */
        public readonly string $scriptUrl = 'https://bundles.veribenim.com/bundle.js',
    ) {
        if (empty($token)) {
            throw new \InvalidArgumentException('[Veribenim] token boş olamaz');
        }
    }
}
