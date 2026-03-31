<?php

declare(strict_types=1);

namespace Veribenim\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Veribenim\VeribenimClient;

/**
 * Belirtilen çerez kategorilerine rıza verilmiş mi kontrol eder.
 *
 * @example routes/web.php:
 * Route::middleware('veribenim.consent:analytics')->group(function () {
 *     Route::get('/dashboard', DashboardController::class);
 * });
 */
class VerifyConsent
{
    public function __construct(private readonly VeribenimClient $client) {}

    public function handle(Request $request, Closure $next, string ...$categories): Response
    {
        $sessionId = $request->cookie('veribenim_session') ?? $request->header('X-Veribenim-Session');
        $redirectTo = config('veribenim.middleware.redirect_to', '/');

        if (!$sessionId) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Çerez rızası gereklidir'], 403)
                : redirect($redirectTo);
        }

        $prefs = $this->client->getPreferences($sessionId);

        if (!$prefs || !isset($prefs['preferences'])) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Çerez rızası gereklidir'], 403)
                : redirect($redirectTo);
        }

        $userPrefs = $prefs['preferences'];
        $requiredCategories = empty($categories)
            ? config('veribenim.middleware.required_categories', ['necessary'])
            : $categories;

        foreach ($requiredCategories as $category) {
            if (empty($userPrefs[$category])) {
                return $request->expectsJson()
                    ? response()->json(['error' => "'{$category}' kategorisi için rıza gereklidir"], 403)
                    : redirect($redirectTo);
            }
        }

        return $next($request);
    }
}
