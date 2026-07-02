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
