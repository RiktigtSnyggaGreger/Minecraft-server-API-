<?php
const MC_DIR    = '/minecraft';
const USERCACHE = MC_DIR . '/usercache.json';
const STATS_DIR = MC_DIR . '/world/players/stats';
const ADVANCEMENTS_DIR = MC_DIR . '/world/players/advancements';

// ============================================================
//  Player
// ============================================================
function get_players() {
    $cashe = read_json(USERCACHE) ?? [];
    $usernames = [];
    foreach ($cashe as $p) {
        $usernames[$p['uuid']] = $p['name'];
    }
    return $usernames;
}

function get_player($id) {
    $uuid = resolve_uuid($id);
    if ($uuid === null) {
        respond(['error' => 'Player not found'], 404);
    }
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

function get_player_list() {
    $names = get_players();
    $list = [];
    foreach ($names as $uuid => $name) {
        $stats = read_json(STATS_DIR . '/' .  $uuid . '.json');    
        $ticks = $stats['stats']['minecraft:custom']['minecraft:play_time'] ?? 0;
        $hours = round($ticks / 72000, 1);
        $list[] = [
            'uuid' => $uuid,
            'name' => $name,
            'play_time_hours' => $hours,
            'advancements' => count(read_advancements($uuid)),
        ];
    }
    return $list;
}

// ============================================================
//  Clean stats, för annars står de minecraft: 
// ============================================================
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
// ============================================================
//  Advancements
// ============================================================
function get_advancements($id) {
    $uuid = resolve_uuid($id);
    if ($uuid === null) {
        respond(['error' => 'Player not found'], 404);
    }
    $names = get_players();
    if (!is_file(ADVANCEMENTS_DIR . '/' .  $uuid . '.json')) {
        respond(['error' => 'Player not found'], 404);
    }

    return [
        'uuid' => $uuid,
        'name' => $names[$uuid] ?? null,
        'advancements' => read_advancements($uuid),
    ];
}
function read_advancements($uuid) {
    $file = read_json(ADVANCEMENTS_DIR . '/' .  $uuid . '.json') ?? [];
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
    return $done;
}


// ============================================================
//  uuid fix
// ============================================================

function find_uuid($name) {
    $names = get_players();
    foreach ($names as $uuid => $n) {
        if (strtolower($n) === strtolower($name)) {
            return $uuid;
        }
    }
    return null;
}

function resolve_uuid($id) {
    if (preg_match('#^[0-9a-f-]{36}$#', $id)) {
        return $id;
    } else {
        return find_uuid($id);
    }
}

