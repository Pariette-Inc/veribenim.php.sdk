<?php

declare(strict_types=1);

namespace Veribenim\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Veribenim\Proxy\ConsentProxy;
use Veribenim\Proxy\ConsentProxyRequest;

/**
 * /_vb/{token}/c ve /_vb/{token}/ping → Veribenim /api/vp/{token}/*.
 * Tüm güvenlik kuralları çekirdekteki ConsentProxy'dedir; burası yalnızca
 * Laravel isteğini/yanıtını çevirir.
 */
class ConsentProxyController
{
    public function __invoke(Request $request, ConsentProxy $proxy): Response
    {
        $result = $proxy->handle(new ConsentProxyRequest(
            method: $request->getMethod(),
            path: '/' . ltrim($request->path(), '/'),
            query: $request->query(),
            headers: $request->headers->all(),
            body: (string) $request->getContent(),
            // TrustProxies'e uyan IP — istemcinin X-Forwarded-For'u doğrudan okunmaz.
            clientIp: $request->ip(),
        ));

        $response = new Response($result->body, $result->status);
        foreach ($result->headers as [$name, $value]) {
            // Symfony Set-Cookie'yi Cookie nesnesine çevirip yeniden yazar;
            // ad, değer, Domain, Path, Max-Age, Secure ve SameSite korunur.
            $response->headers->set($name, $value, false);
        }

        return $response;
    }
}
