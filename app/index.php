<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($method === 'GET' && $path === '/') {
    echo json_encode(['status' => 'ok', 'database' => $db->server_info]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}