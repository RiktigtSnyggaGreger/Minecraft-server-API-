<?php
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
