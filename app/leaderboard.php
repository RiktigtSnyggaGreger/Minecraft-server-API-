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
        $name = $names[$uuid] ?? null;
        $stats = read_json(STATS_DIR . '/' . $uuid . '.json');
        $custom = $stats['stats']['minecraft:custom'] ?? [];
        $value = match ($type) {
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
        $list[] = [
            'uuid' => $uuid,
            'name' => $name,
            'value' => $value,
        ];
    }
    usort($list, function ($a, $b) {
        return $b['value'] <=> $a['value'];
    });
    return $list;
}

function get_all_leaderboards() {
    $all = [];
    foreach (LEADERBOARDS as $type) {
        $all[$type] = array_slice(get_leaderboard($type), 0, 3);
    }
    return $all;
}
