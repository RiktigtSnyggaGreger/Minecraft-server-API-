<?php
const MC_DIR    = '/minecraft';
const USERCACHE = MC_DIR . '/usercache.json';
const STATS_DIR = MC_DIR . '/world/players/stats';

function get_players() {
    $cashe = read_json(USERCACHE) ?? [];
    $usernames = [];
    foreach ($cashe as $p) {
        $usernames[$p['uuid']] = $p['name'];
    }
    return $usernames;
}

function get_player($uuid) {
    $names = get_players();
    $stats = read_json(STATS_DIR . '/' .  $uuid . '.json');
    if ($stats === null) {
        respond(['error' => 'Player not found'], 404);
    }
    return [
        'uuid' => $uuid,
        'name' => $names[$uuid] ?? null,
        'stats' => $stats['stats'],
    ];
}