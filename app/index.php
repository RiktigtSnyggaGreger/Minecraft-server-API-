<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/db.php';
require __DIR__ . '/player.php';

// ============================================================
//  Hjälpfunktioner
// ============================================================
function respond(mixed $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json(string $file): ?array
{
    if (!is_file($file)) return null;
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

// ============================================================
//  Router
// ============================================================
$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

if ($method === 'GET' && $path === '/players') {
    respond(get_players());
}

if ($method === 'GET' && preg_match('#^/players/([0-9a-f-]{36})$#', $path, $m)) {
    respond(get_player($m[1]));
}

// Ingen route matchade
respond(['error' => 'Not found'], 404);