<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/helper.php';
require __DIR__ . '/db.php';
require __DIR__ . '/player.php';

// ============================================================
//  Router
// ============================================================
$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

if ($method === 'GET' && $path === '/players') {
    respond(get_player_list());
}

if ($method === 'GET' && preg_match('#^/players/([\w-]+)$#', $path, $m)) {
    respond(get_player($m[1]));
}

if ($method === 'GET' && preg_match('#^/players/([\w-]+)/advancements$#', $path, $m)) {
    respond(get_advancements($m[1]));
}

respond(['error' => 'Not found'], 404);
