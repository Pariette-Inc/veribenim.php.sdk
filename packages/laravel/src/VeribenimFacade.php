<?php

declare(strict_types=1);

namespace Veribenim\Laravel;

use Illuminate\Support\Facades\Facade;
use Veribenim\VeribenimClient;

/**
 * @method static bool logImpression(array $payload = [])
 * @method static bool logConsent(string $action, ?array $preferences = null, ?string $sessionId = null)
 * @method static array|null getPreferences(?string $sessionId = null)
 * @method static array|null savePreferences(array $preferences, ?string $sessionId = null)
 * @method static string scriptTag()
 * @method static array|null getFormSchema(string $slug)
 * @method static array|null submitForm(string $slug, array $data)
 * @method static string renderFormHtml(string $slug, array $options = [])
 * @method static array|null submitDsar(string $requestType, string $fullName, string $email, string $description = '')
 * @method static bool collectAnalytics(array $payload)
 * @method static array|null scanCookies(string $url)
 * @method static array|null verifyDomain(?string $domain = null)
 * @method static string impressionPixelUrl(array $opts = [])
 *
 * @see VeribenimClient
 */
class VeribenimFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'veribenim';
    }
}
