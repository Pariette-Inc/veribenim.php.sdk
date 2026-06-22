<?php

declare(strict_types=1);

namespace Veribenim\Laravel;

use Veribenim\VeribenimClient;

/**
 * Ziyaretçinin çerez rıza durumunu kategori bazında kontrol eder.
 *
 * @ifConsented('analytics') Blade direktifi ve programatik kullanım için.
 * Rıza durumu veribenim_session çerezi (veya X-Veribenim-Session header)
 * üzerinden okunur ve istek başına önbelleğe alınır.
 */
class ConsentHelper
{
    /** @var array<string, array<string,bool>|null> sessionId => preferences */
    private array $cache = [];

    public function __construct(private readonly VeribenimClient $client) {}

    /**
     * Verilen kategorilerin TÜMÜNE rıza verilmiş mi?
     * Parametre verilmezse config'teki required_categories (varsayılan: necessary) kullanılır.
     */
    public function isConsented(string ...$categories): bool
    {
        if (empty($categories)) {
            $categories = (array) config('veribenim.middleware.required_categories', ['strictly_necessary']);
        }

        $prefs = $this->preferences();
        if ($prefs === null) {
            return false;
        }

        foreach ($categories as $category) {
            if (empty($prefs[$category])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mevcut isteğin rıza tercihlerini döner (kategori => bool) veya null.
     *
     * @return array<string,bool>|null
     */
    public function preferences(): ?array
    {
        $request = function_exists('request') ? request() : null;
        $sessionId = $request?->cookie('veribenim_session')
            ?? $request?->header('X-Veribenim-Session');

        if (!$sessionId) {
            return null;
        }

        if (array_key_exists($sessionId, $this->cache)) {
            return $this->cache[$sessionId];
        }

        $response = $this->client->getPreferences($sessionId);

        return $this->cache[$sessionId] = ($response['current_consents'] ?? null);
    }
}
