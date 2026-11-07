<?php
const MC_DIR    = '/minecraft';
const USERCACHE = MC_DIR . '/usercache.json';
const STATS_DIR = MC_DIR . '/world/players/stats';
const ADVANCEMENTS_DIR = MC_DIR . '/world/players/advancements';

// Antal advancements i spelet (utan recept), räknat i serverns jar-fil för 26.3.
// Uppdatera när Minecraft uppdateras!
const ADVANCEMENTS_TOTAL = 126;

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

// Alla som har spelat = alla som har en stats-fil.
// (usercache kan sakna gamla spelare, så den används bara för namnen)
function get_uuids() {
    $uuids = [];
    foreach (glob(STATS_DIR . '/*.json') as $file) {
        $uuids[] = basename($file, '.json');
    }
    return $uuids;
}

function get_player($id) {
    $uuid = resolve_uuid($id);
    if ($uuid === null) {
        respond(['error' => 'Player not found'], 404);
    }
    $names = get_players();
    $name = $names[$uuid] ?? null;
    $file = STATS_DIR . '/' .  $uuid . '.json';
    $stats = read_json($file);
    if ($stats === null && $name === null) {
        respond(['error' => 'Player not found'], 404);
    }
    $ticks = $stats['stats']['minecraft:custom']['minecraft:play_time'] ?? 0;
        
    $hours = round($ticks / 72000, 1);

    $last_seen = null;
    if (is_file($file)) {
        $last_seen = date('c', filemtime($file));
    }
    
    return [
        'uuid' => $uuid,
        'name' => $name,
        'play_time_hours' => $hours,
        'last_seen' => $last_seen,
        'stats' => clean_stats($stats['stats'] ?? []),
    ];
}

function get_player_list() {
    $names = get_players();
    $list = [];

    foreach (get_uuids() as $uuid) {
        $name = $names[$uuid] ?? null;
        $file = STATS_DIR . '/' .  $uuid . '.json';
        $stats = read_json($file);  
        $last_seen = null;  
        if (is_file($file)) {
            $last_seen = date('c', filemtime($file));
        }
        $ticks = $stats['stats']['minecraft:custom']['minecraft:play_time'] ?? 0;
        $hours = round($ticks / 72000, 1);
        $list[] = [
            'uuid' => $uuid,
            'name' => $name,
            'play_time_hours' => $hours,
            'last_seen' => $last_seen,
            'advancements' => count(read_advancements($uuid)),
            'advancements_total' => ADVANCEMENTS_TOTAL,
        ];
    }

    

    // En lätt sort.. Sortar den som har mest timmar
    usort($list, function ($a, $b) {
        return $b['play_time_hours'] <=> $a['play_time_hours'];
    });

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
    $done = read_advancements($uuid);

    return [
        'uuid' => $uuid,
        'name' => $names[$uuid] ?? null,
        'count' => count($done),
        'total' => ADVANCEMENTS_TOTAL,
        'advancements' => $done,
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

