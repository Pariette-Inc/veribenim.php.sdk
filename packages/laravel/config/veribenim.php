<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Environment Token
    |--------------------------------------------------------------------------
    | Veribenim panelinden alınan site token'ı.
    | Panelde: Siteniz → Entegrasyon → Token
    */
    'token' => env('VERIBENIM_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Site Domain
    |--------------------------------------------------------------------------
    | Bundle script URL'ini oluşturmak için site domain'i.
    | Örn: 'claude.com', 'example.com'
    | Boş bırakılırsa @veribenimScript direktifi çalışmaz.
    */
    'domain' => env('VERIBENIM_DOMAIN', ''),

    /*
    |--------------------------------------------------------------------------
    | Dil
    |--------------------------------------------------------------------------
    | 'tr' veya 'en'. Varsayılan: 'tr'
    */
    'lang' => env('VERIBENIM_LANG', 'tr'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    */
    'timeout' => env('VERIBENIM_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Debug
    |--------------------------------------------------------------------------
    */
    'debug' => env('VERIBENIM_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Adresler (On-Prem)
    |--------------------------------------------------------------------------
    | Varsayılanlar bugünkü bulut kurulumudur; yalnızca kurum içi kurulumda
    | değiştirin. Boş bırakılırsa varsayılan kullanılır.
    */
    'api_url'         => env('VERIBENIM_API_URL', \Veribenim\VeribenimConfig::DEFAULT_API_URL),
    'bundle_base_url' => env('VERIBENIM_BUNDLE_BASE_URL', \Veribenim\VeribenimConfig::DEFAULT_BUNDLE_BASE_URL),
    'web_url'         => env('VERIBENIM_WEB_URL', \Veribenim\VeribenimConfig::DEFAULT_WEB_URL),

    /*
    |--------------------------------------------------------------------------
    | Birinci Taraf Rıza Proxy'si (/_vb/*)
    |--------------------------------------------------------------------------
    | Açıkken sitenizde POST /_vb/{token}/c ve GET /_vb/{token}/ping rotaları
    | kaydedilir ve Veribenim'in /api/vp/{token}/* uçlarına iletilir. Rıza
    | çerezi sitenizin kendi alan adından yazılır (Safari ITP 7 gün sınırı yok).
    |
    | proxy_key: Konsol → Entegrasyon → Birinci Taraf Toplama kartındaki anahtar.
    | YALNIZCA .env'de tutun; tarayıcıya, view'lara ya da loglara yazmayın.
    |
    | Ziyaretçi IP'si $request->ip()'den alınır: CDN / yük dengeleyici
    | arkasındaysanız Laravel'in TrustProxies ayarını doğru yapın; aksi hâlde
    | rıza kaydına proxy sunucusunun IP'si yazılır.
    */
    'consent_proxy' => [
        'enabled'   => env('VERIBENIM_PROXY_ENABLED', false),
        'proxy_key' => env('VERIBENIM_PROXY_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    | Consent zorunlu rotalar için middleware ayarları.
    */
    'middleware' => [
        'redirect_to' => '/',
        'required_categories' => ['strictly_necessary'],
    ],
];
