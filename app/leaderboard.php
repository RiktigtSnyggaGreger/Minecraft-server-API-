<?php
const LEADERBOARDS = [
    'play_time',
    'longest_life',
    'deaths',
    'mob_kills',
    'damage_dealt',
    'walked_blocks',
    'blocks_mined',
    'items_crafted',
];

// Kategorier man kan göra topplistor per sak i, t.ex. /leaderboard/mined/diamond_ore
const STAT_CATEGORIES = [
    'mined',
    'crafted',
    'used',
    'broken',
    'picked_up',
    'dropped',
    'killed',
    'killed_by',
];

// ============================================================
//  Räknar ut ETT värde för EN spelare (används av både topplistor och /stats)
// ============================================================
function leaderboard_value($type, $stats) {
    $custom = $stats['stats']['minecraft:custom'] ?? [];
    return match ($type) {
        // Tid (ticks → timmar)
        'play_time'     => round(($custom['minecraft:play_time'] ?? 0) / 72000, 1),
        'longest_life'  => round(($custom['minecraft:time_since_death'] ?? 0) / 72000, 1),

        // Strid
        'deaths'        => $custom['minecraft:deaths'] ?? 0,
        'mob_kills'     => $custom['minecraft:mob_kills'] ?? 0,
        'damage_dealt'  => round(($custom['minecraft:damage_dealt'] ?? 0) / 10),

        // Förflyttning
        'walked_blocks' => round(($custom['minecraft:walk_one_cm'] ?? 0) / 100),

        // Totaler
        'blocks_mined'  => array_sum($stats['stats']['minecraft:mined'] ?? []),
        'items_crafted' => array_sum($stats['stats']['minecraft:crafted'] ?? []),
    };
}

// Sorterar en lista så att högst 'value' kommer först
function sort_by_value($list) {
    usort($list, function ($a, $b) {
        return $b['value'] <=> $a['value'];
    });
    return $list;
}

// ============================================================
//  Leaderboard!
// ============================================================
function get_leaderboard($type) {
    $type = strtolower($type);
    if (!in_array($type, LEADERBOARDS)) {
        respond(['error' => 'Leaderboard not found'], 404);
    }
    $names = get_players();
    $list = [];
    foreach (get_uuids() as $uuid) {
        $stats = read_json(STATS_DIR . '/' . $uuid . '.json');
        $list[] = [
            'uuid' => $uuid,
            'name' => $names[$uuid] ?? null,
            'value' => leaderboard_value($type, $stats),
        ];
    }
    return sort_by_value($list);
}

function get_all_leaderboards() {
    $all = [];
    foreach (LEADERBOARDS as $type) {
        $all[$type] = array_slice(get_leaderboard($type), 0, 3);
    }
    return $all;
}

// ============================================================
//  Topplista per sak, t.ex. /leaderboard/mined/diamond_ore
// ============================================================
function get_item_leaderboard($category, $item) {
    $category = strtolower($category);
    $item = strtolower($item);
    if (!in_array($category, STAT_CATEGORIES)) {
        respond(['error' => 'Leaderboard not found'], 404);
    }
    $names = get_players();
    $list = [];
    foreach (get_uuids() as $uuid) {
        $stats = read_json(STATS_DIR . '/' . $uuid . '.json');
        $list[] = [
            'uuid' => $uuid,
            'name' => $names[$uuid] ?? null,
            'value' => $stats['stats']['minecraft:' . $category]['minecraft:' . $item] ?? 0,
        ];
    }
    return sort_by_value($list);
}

// ============================================================
//  Servertotaler, /stats
// ============================================================
function get_server_stats() {
    // Att lägga ihop "tid sedan senaste dödsfall" för alla blir meningslöst
    $types = array_diff(LEADERBOARDS, ['longest_life']);

    $totals = [];
    foreach ($types as $type) {
        $totals[$type] = 0;
    }

    $uuids = get_uuids();
    foreach ($uuids as $uuid) {
        $stats = read_json(STATS_DIR . '/' . $uuid . '.json');
        foreach ($types as $type) {
            $totals[$type] += leaderboard_value($type, $stats);
        }
    }

    // Avrunda igen, annars kan det bli t.ex. 15.600000000000001
    foreach ($totals as $type => $value) {
        $totals[$type] = round($value, 1);
    }

    return ['players' => count($uuids)] + $totals;
}
