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
    | Middleware
    |--------------------------------------------------------------------------
    | Consent zorunlu rotalar için middleware ayarları.
    */
    'middleware' => [
        'redirect_to' => '/',
        'required_categories' => ['strictly_necessary'],
    ],
];
