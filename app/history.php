<?php
// ============================================================
//  Historik från databasen (fylls på av snapshot.php varje timme)
// ============================================================

// GET /players/{id}/history, en rad per dag, äldst först
function get_player_history($db, $id) {
    $uuid = resolve_uuid($id);
    if ($uuid === null) {
        respond(['error' => 'Player not found'], 404);
    }

    $stmt = $db->prepare('
        SELECT day, play_time, deaths, mob_kills, damage_dealt, walked_cm, blocks_mined, items_crafted
        FROM snapshots
        WHERE uuid = ?
        ORDER BY day
    ');
    $stmt->bind_param('s', $uuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Ingen historik OCH ingen stats-fil → spelaren finns inte
    if (count($rows) === 0 && !is_file(STATS_DIR . '/' . $uuid . '.json')) {
        respond(['error' => 'Player not found'], 404);
    }

    // Räkna om rådata till samma enheter som topplistorna
    $history = [];
    foreach ($rows as $row) {
        $history[] = [
            'day'           => $row['day'],
            'play_time'     => round($row['play_time'] / 72000, 1),   // ticks → timmar
            'deaths'        => (int) $row['deaths'],
            'mob_kills'     => (int) $row['mob_kills'],
            'damage_dealt'  => round($row['damage_dealt'] / 10),      // → hjärtan
            'walked_blocks' => round($row['walked_cm'] / 100),        // cm → block
            'blocks_mined'  => (int) $row['blocks_mined'],
            'items_crafted' => (int) $row['items_crafted'],
        ];
    }

    $names = get_players();
    return [
        'uuid' => $uuid,
        'name' => $names[$uuid] ?? null,
        'history' => $history,
    ];
}
