<?php

declare(strict_types=1);

/**
 * WordPress fonksiyon stub'ları — unit testler için minimal implementasyonlar.
 * Durum, VeribenimWpTestState üzerinden test başına sıfırlanır/ayarlanır.
 */

class VeribenimWpTestState
{
    /** @var array<string, mixed> get_option deposu */
    public static array $options = [];

    public static bool $isAdmin = false;

    public static bool $currentUserCan = true;

    public static string $homeUrl = 'https://claude.com';

    /** wp_remote_get'in döneceği yanıt (array ya da WP_Error) */
    public static mixed $remoteResponse = null;

    /** @var array<int, array{url:string, args:array}> */
    public static array $remoteRequests = [];

    /** @var array<int, array> add_settings_error kayıtları */
    public static array $settingsErrors = [];

    /** @var array<string, array> add_action kayıtları */
    public static array $actions = [];

    /** @var array<string, callable> add_shortcode kayıtları */
    public static array $shortcodes = [];

    public static function reset(): void
    {
        self::$options        = [];
        self::$isAdmin        = false;
        self::$currentUserCan = true;
        self::$homeUrl        = 'https://claude.com';
        self::$remoteResponse = null;
        self::$remoteRequests = [];
        self::$settingsErrors = [];
        self::$actions        = [];
        self::$shortcodes     = [];

        self::bindFallbackTranslator();
    }

    /**
     * Monorepo test ortamında global __() helper'ı Laravel'den gelir
     * (composer autoload, bootstrap'tan önce yüklenir). WP kodundaki
     * __('metin', 'veribenim') çağrılarının patlamaması için container'a
     * anahtarı aynen döndüren minimal bir translator bağlanır.
     */
    private static function bindFallbackTranslator(): void
    {
        $container = \Illuminate\Container\Container::getInstance();

        if (! $container->bound('translator')) {
            $container->instance('translator', new class {
                public function get($key, $replace = [], $locale = null)
                {
                    return $key;
                }

                public function choice($key, $number, $replace = [], $locale = null)
                {
                    return $key;
                }
            });
        }
    }
}

class WP_Error
{
    public function __construct(
        private string $code = 'error',
        private string $message = 'WP error',
    ) {}

    public function get_error_message(): string
    {
        return $this->message;
    }
}

// --- Çeviri --------------------------------------------------------------
// __() bootstrap'ta (vendor autoload'dan önce) tanımlanır.

if (! function_exists('_e')) {
    function _e($text, $domain = 'default'): void
    {
        echo $text;
    }
}

if (! function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default')
    {
        return htmlspecialchars((string) $text, ENT_QUOTES);
    }
}

// --- Escaping ------------------------------------------------------------

if (! function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES);
    }
}

if (! function_exists('esc_html')) {
    function esc_html($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES);
    }
}

if (! function_exists('esc_url')) {
    function esc_url($url)
    {
        return (string) $url;
    }
}

if (! function_exists('esc_url_raw')) {
    function esc_url_raw($url)
    {
        return (string) $url;
    }
}

if (! function_exists('esc_js')) {
    function esc_js($text)
    {
        return str_replace(
            ['\\', "'", '"', "\r", "\n"],
            ['\\\\', "\\'", '&quot;', '', ''],
            (string) $text
        );
    }
}

if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field($str)
    {
        return trim(strip_tags((string) $str));
    }
}

// --- Options -------------------------------------------------------------

if (! function_exists('get_option')) {
    function get_option($name, $default = false)
    {
        return VeribenimWpTestState::$options[$name] ?? $default;
    }
}

if (! function_exists('update_option')) {
    function update_option($name, $value): bool
    {
        VeribenimWpTestState::$options[$name] = $value;

        return true;
    }
}

// --- Hooks / Shortcodes ----------------------------------------------------

if (! function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1): void
    {
        VeribenimWpTestState::$actions[$hook][] = ['callback' => $callback, 'priority' => $priority];
    }
}

if (! function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback): void
    {
        VeribenimWpTestState::$shortcodes[$tag] = $callback;
    }
}

if (! function_exists('shortcode_atts')) {
    function shortcode_atts(array $defaults, array $atts, $shortcode = ''): array
    {
        $out = [];
        foreach ($defaults as $name => $default) {
            $out[$name] = array_key_exists($name, $atts) ? $atts[$name] : $default;
        }

        return $out;
    }
}

// --- Ortam ---------------------------------------------------------------

if (! function_exists('is_admin')) {
    function is_admin(): bool
    {
        return VeribenimWpTestState::$isAdmin;
    }
}

if (! function_exists('current_user_can')) {
    function current_user_can($capability): bool
    {
        return VeribenimWpTestState::$currentUserCan;
    }
}

if (! function_exists('home_url')) {
    function home_url($path = ''): string
    {
        return VeribenimWpTestState::$homeUrl . $path;
    }
}

// --- HTTP ----------------------------------------------------------------

if (! function_exists('wp_remote_get')) {
    function wp_remote_get($url, array $args = [])
    {
        VeribenimWpTestState::$remoteRequests[] = ['url' => $url, 'args' => $args];

        return VeribenimWpTestState::$remoteResponse
            ?? new WP_Error('http_request_failed', 'Yanıt tanımlanmadı');
    }
}

if (! function_exists('is_wp_error')) {
    function is_wp_error($thing): bool
    {
        return $thing instanceof WP_Error;
    }
}

if (! function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response)
    {
        if ($response instanceof WP_Error || ! is_array($response)) {
            return '';
        }

        return $response['response']['code'] ?? '';
    }
}

if (! function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response): string
    {
        if ($response instanceof WP_Error || ! is_array($response)) {
            return '';
        }

        return $response['body'] ?? '';
    }
}

// --- Settings API ----------------------------------------------------------

if (! function_exists('add_settings_error')) {
    function add_settings_error($setting, $code, $message, $type = 'error'): void
    {
        VeribenimWpTestState::$settingsErrors[] = compact('setting', 'code', 'message', 'type');
    }
}
