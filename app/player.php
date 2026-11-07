<?php
const MC_DIR    = '/minecraft';
const USERCACHE = MC_DIR . '/usercache.json';
const STATS_DIR = MC_DIR . '/world/players/stats';
const ADVANCEMENTS_DIR = MC_DIR . '/world/players/advancements';

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
    $ticks = $stats['stats']['minecraft:custom']['minecraft:play_time'] ?? 0;
    
    $hours = round($ticks / 72000, 1);
    return [
        'uuid' => $uuid,
        'name' => $names[$uuid] ?? null,
        'play_time_hours' => $hours,
        'stats' => clean_stats($stats['stats']),
    ];
}

function clean_stats($stats) {
    $clean = [];
    foreach ($stats as $category => $items) {
        $cat = str_replace('minecraft:', '', $category);
        foreach ($items as $item => $count) {
            $name = str_replace('minecraft:', '', $item);
            $clean[$cat][$name] = $count;
        }
    }
    return $clean;
}

function get_advancements($uuid) {
    $names = get_players();
    $file = read_json(ADVANCEMENTS_DIR . '/' .  $uuid . '.json');
    if ($file === null) {
        respond(['error' => 'Player not found'], 404);
    }
    $done = [];
    foreach ($file as $key => $adv) {
        if ($key === 'DataVersion') {
            continue;
        }
        if (str_contains($key, 'recipes/')) {
            continue;
        }
        if ($adv['done'] === true) {
            $name = str_replace('minecraft:', '', $key);
            $date = max($adv['criteria']);
            $done[$name] = $date;
        }
    }
    return [
        'uuid' => $uuid,
        'name' => $names[$uuid] ?? null,
        'advancements' => $done,
    ];
}
