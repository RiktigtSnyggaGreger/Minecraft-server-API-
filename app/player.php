<?php
// Minecraft-datan är monterad här (se docker-compose.yml)
const MC_DIR    = '/minecraft';
const STATS_DIR = MC_DIR . '/world/players/stats';
const USERCACHE = MC_DIR . '/usercache.json';

/** Alla spelare som har spelat: [uuid => namn] */
function get_players(): array
{
    $names = [];
    foreach (read_json(USERCACHE) ?? [] as $p) {
        $names[$p['uuid']] = $p['name'];
    }

    $players = [];
    foreach (glob(STATS_DIR . '/*.json') as $file) {
        $uuid = basename($file, '.json');
        $players[$uuid] = $names[$uuid] ?? $uuid;
    }
    return $players;
}

/** En spelares stats */
function get_player_stats(string $uuid): array
{
    $data  = read_json(STATS_DIR . "/$uuid.json");
    $stats = $data['stats']['minecraft:custom'] ?? [];

    return [
        'play_hours' => round(($stats['minecraft:play_time'] ?? 0) / 20 / 3600, 1),
        'deaths'     => $stats['minecraft:deaths'] ?? 0,
        'mob_kills'  => $stats['minecraft:mob_kills'] ?? 0,
    ];
}