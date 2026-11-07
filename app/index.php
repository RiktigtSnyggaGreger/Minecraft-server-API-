<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/helper.php';
require __DIR__ . '/db.php';
require __DIR__ . '/player.php';
require __DIR__ . '/leaderboard.php'; 

// ============================================================
//  Router
// ============================================================
$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

if ($method === 'GET' && $path === '/') {

    if (!is_file(USERCACHE)) {
        respond(['error' => 'Minecraft data unavailable'], 503);
    }
    respond([
        'status' => 'ok',
        'database' => 'up',
        'minecraft_data' => 'up',
    ]);
}

if ($method === 'GET' && $path === '/players') {
    respond(get_player_list());
}

if ($method === 'GET' && preg_match('#^/players/([\w-]+)$#', $path, $m)) {
    respond(get_player($m[1]));
}

if ($method === 'GET' && preg_match('#^/players/([\w-]+)/advancements$#', $path, $m)) {
    respond(get_advancements($m[1]));
}

if ($method === 'GET' && $path === '/leaderboard') {
    respond(get_all_leaderboards());
}

if ($method === 'GET' && preg_match('#^/leaderboard/(\w+)$#', $path, $m)) {
    respond(get_leaderboard($m[1]));
}
respond(['error' => 'Not found'], 404);
