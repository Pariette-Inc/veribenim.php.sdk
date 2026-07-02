<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Monorepo test bootstrap
|--------------------------------------------------------------------------
| Not: Global __() helper'ı Laravel'den gelir (PHPUnit, composer autoload'ı
| bootstrap'tan önce yükler). WordPress testlerindeki __() çağrıları için
| wp-stubs.php container'a fallback bir translator bağlar.
*/
require __DIR__ . '/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| WordPress test ortamı
|--------------------------------------------------------------------------
| Eklenti dosyaları "defined('ABSPATH') || exit" guard'ı içerdiğinden
| ABSPATH stub'ı ve WP fonksiyon stub'ları sınıflardan önce yüklenir.
*/
if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../packages/wordpress/');
}

require __DIR__ . '/../packages/wordpress/tests/Support/wp-stubs.php';
require __DIR__ . '/../packages/wordpress/includes/class-frontend.php';
require __DIR__ . '/../packages/wordpress/includes/class-admin.php';
require __DIR__ . '/../packages/wordpress/includes/class-plugin.php';
