<?php
// ============================================================
//  Sparar en ögonblicksbild av allas stats i databasen.
//  Kör: docker compose exec php php /var/www/html/snapshot.php
// ============================================================

// Får bara köras från terminalen, aldrig via webben
if (PHP_SAPI !== 'cli') {
    exit;
}

require __DIR__ . '/helper.php';
require __DIR__ . '/db.php';
require __DIR__ . '/player.php';

$today = date('Y-m-d');

// 1. Förbered SQL:en EN gång, och kör den sedan för varje spelare
$stmt = $db->prepare('
    INSERT INTO snapshots
        (uuid, day, play_time, deaths, mob_kills, damage_dealt, walked_cm, blocks_mined, items_crafted, stats)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        play_time     = VALUES(play_time),
        deaths        = VALUES(deaths),
        mob_kills     = VALUES(mob_kills),
        damage_dealt  = VALUES(damage_dealt),
        walked_cm     = VALUES(walked_cm),
        blocks_mined  = VALUES(blocks_mined),
        items_crafted = VALUES(items_crafted),
        stats         = VALUES(stats)
');

$count = 0;

// 2. Loopa över alla som har en stats-fil
foreach (get_uuids() as $uuid) {
    $file = read_json(STATS_DIR . '/' . $uuid . '.json');
    $stats = $file['stats'] ?? [];
    $custom = $stats['minecraft:custom'] ?? [];

    // Rådata, INTE omräknat (ticks, cm osv.)
    $play_time     = $custom['minecraft:play_time'] ?? 0;
    $deaths        = $custom['minecraft:deaths'] ?? 0;
    $mob_kills     = $custom['minecraft:mob_kills'] ?? 0;
    $damage_dealt  = $custom['minecraft:damage_dealt'] ?? 0;
    $walked_cm     = $custom['minecraft:walk_one_cm'] ?? 0;
    $blocks_mined  = array_sum($stats['minecraft:mined'] ?? []);
    $items_crafted = array_sum($stats['minecraft:crafted'] ?? []);
    $stats_json    = json_encode($stats);

    // 3. Spara: s = text, i = heltal (10 st, i samma ordning som ? ovan)
    $stmt->bind_param(
        'ssiiiiiiis',
        $uuid, $today,
        $play_time, $deaths, $mob_kills, $damage_dealt,
        $walked_cm, $blocks_mined, $items_crafted,
        $stats_json
    );
    $stmt->execute();
    $count++;
}

echo "Sparade $count spelare för $today\n";
