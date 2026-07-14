<?php
/*
 * ============================================================
 *  Made by Bapan | Date: 5/4/2026
 *  All credits belongs to Bapan
 *  For any kind of software development job, cheat, website
 *  or panel development — contact Bapan:
 *  Telegram: https://t.me/bapanff
 *  Official Channel: https://t.me/mocosn
 * ============================================================
 */
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$pdo = Database::connect();
$features = $pdo->query("SELECT name, category, type, toggle_id, default_value, min_value, max_value, step, unit FROM client_features WHERE is_active = 1 ORDER BY category, sort_order, id")->fetchAll();

$grouped = [];
foreach ($features as $f) {
    $cat = $f['category'];
    if (!isset($grouped[$cat])) $grouped[$cat] = [];

    $item = [
        'name' => $f['name'],
        'type' => $f['type'],
        'tid' => (int)$f['toggle_id'],
        'default' => $f['type'] === 'slider' ? (float)$f['default_value'] : (int)$f['default_value']
    ];

    if ($f['type'] === 'slider') {
        $item['min'] = (int)$f['min_value'];
        $item['max'] = (int)$f['max_value'];
        if ($f['step']) $item['step'] = (float)$f['step'];
        if ($f['unit']) $item['unit'] = $f['unit'];
    }

    $grouped[$cat][] = $item;
}

echo json_encode(['success' => true, 'features' => $grouped]);
