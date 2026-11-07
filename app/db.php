<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli(
        getenv('DB_HOST'),
        getenv('DB_USER'),
        getenv('DB_PASSWORD'),
        getenv('DB_NAME')
    );
$db->set_charset('utf8mb4');

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());   // hamnar i: docker compose logs php
    http_response_code(500);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}