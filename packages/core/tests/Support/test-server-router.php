<?php

declare(strict_types=1);

/**
 * PHP built-in server router — VeribenimClientHttpTest tarafından kullanılır.
 * Path'teki token'a göre farklı HTTP senaryoları simüle eder.
 */

$uri = $_SERVER['REQUEST_URI'] ?? '/';

// Web analytics beacon ucu: 204 No Content
if (preg_match('#^/api/v/[^/]+/e$#', parse_url($uri, PHP_URL_PATH) ?: '')) {
    http_response_code(204);
    exit;
}

// Rıza proxy upstream'i (/api/vp/{token}/c|ping) — CurlProxyTransport testleri
if (preg_match('#^/api/vp/([^/]+)/(c|ping)$#', parse_url($uri, PHP_URL_PATH) ?: '', $m)) {
    if (str_contains($m[1], 'Redir')) {
        http_response_code(302);
        header('Location: https://evil.example/');
        exit;
    }
    http_response_code(200);
    header('Content-Type: application/json');
    header('Set-Cookie: vb_consent_' . substr(hash('sha256', 'vb-consent-cookie|' . $m[1]), 0, 8) . '=eyJ2IjoxfQ; Path=/; Max-Age=600; SameSite=Lax', false);
    header('Set-Cookie: upstream_other=1; Path=/', false);
    header('X-Upstream-Secret: 1');
    echo json_encode([
        'method'    => $_SERVER['REQUEST_METHOD'],
        'query'     => $_SERVER['QUERY_STRING'] ?? '',
        'key'       => $_SERVER['HTTP_X_VB_PROXY_KEY'] ?? null,
        'client_ip' => $_SERVER['HTTP_X_VB_CLIENT_IP'] ?? null,
        'cookie'    => $_SERVER['HTTP_COOKIE'] ?? null,
        'xff'       => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        'body'      => (string) file_get_contents('php://input'),
    ]);
    exit;
}

if (str_contains($uri, 'err404')) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found']);
    exit;
}

if (str_contains($uri, 'err500')) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server Error']);
    exit;
}

if (str_contains($uri, 'badjson')) {
    http_response_code(200);
    header('Content-Type: application/json');
    echo 'bu json degil {';
    exit;
}

if (str_contains($uri, 'slowtok')) {
    sleep(3);
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['slow' => true]);
    exit;
}

// Varsayılan: isteği yansıtan başarılı JSON yanıtı
http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'status' => true,
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri'    => $uri,
    'body'   => json_decode((string) file_get_contents('php://input'), true),
]);
