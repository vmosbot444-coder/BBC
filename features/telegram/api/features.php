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
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';

Auth::requireAdmin();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$pdo = Database::connect();

switch ($action) {
    case 'list':
        $features = $pdo->query("SELECT * FROM client_features ORDER BY category, sort_order, id")->fetchAll();
        // Group by category for the panel
        $categories = [];
        foreach ($features as $f) {
            $cat = $f['category'];
            if (!isset($categories[$cat])) $categories[$cat] = [];
            $categories[$cat][] = $f;
        }
        Response::success(['features' => $features, 'categories' => $categories]);
        break;

    case 'create':
        if ($method !== 'POST') Response::error('method_not_allowed', 405);
        $input = Response::input();
        $name = trim($input['name'] ?? '');
        $category = trim($input['category'] ?? '');
        $type = $input['type'] ?? 'toggle';
        $toggleId = (int)($input['toggle_id'] ?? 0);
        $defaultValue = $input['default_value'] ?? '0';
        $minValue = (float)($input['min_value'] ?? 0);
        $maxValue = (float)($input['max_value'] ?? 100);
        $step = (float)($input['step'] ?? 1);
        $unit = trim($input['unit'] ?? '');
        $sortOrder = (int)($input['sort_order'] ?? 0);

        if (!$name) Response::error('missing_name');
        if (!$category) Response::error('missing_category');
        if (!$toggleId) Response::error('missing_toggle_id');

        // Check toggle_id uniqueness
        $existing = $pdo->prepare("SELECT id FROM client_features WHERE toggle_id = ?");
        $existing->execute([$toggleId]);
        if ($existing->fetch()) Response::error('toggle_id_exists');

        $stmt = $pdo->prepare("INSERT INTO client_features (name, category, type, toggle_id, default_value, min_value, max_value, step, unit, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category, $type, $toggleId, $defaultValue, $minValue, $maxValue, $step, $unit, $sortOrder]);

        Response::logActivity('feature_created', ['name' => $name, 'toggle_id' => $toggleId]);
        Response::success(['id' => (int)$pdo->lastInsertId()]);
        break;

    case 'update':
        if ($method !== 'POST') Response::error('method_not_allowed', 405);
        $input = Response::input();
        $id = (int)($input['id'] ?? 0);
        if (!$id) Response::error('missing_id');

        $fields = [];
        $params = [];
        foreach (['name', 'category', 'type', 'default_value', 'unit'] as $f) {
            if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = $input[$f]; }
        }
        foreach (['toggle_id', 'sort_order'] as $f) {
            if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = (int)$input[$f]; }
        }
        foreach (['min_value', 'max_value', 'step'] as $f) {
            if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = (float)$input[$f]; }
        }
        foreach (['is_active'] as $f) {
            if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = (int)$input[$f]; }
        }

        if (empty($fields)) Response::error('nothing_to_update');
        $params[] = $id;
        $pdo->prepare("UPDATE client_features SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
        Response::logActivity('feature_updated', ['id' => $id]);
        Response::success();
        break;

    case 'delete':
        if ($method !== 'POST') Response::error('method_not_allowed', 405);
        $input = Response::input();
        $id = (int)($input['id'] ?? 0);
        if (!$id) Response::error('missing_id');
        $pdo->prepare("DELETE FROM client_features WHERE id = ?")->execute([$id]);
        Response::logActivity('feature_deleted', ['id' => $id]);
        Response::success();
        break;

    case 'toggle':
        if ($method !== 'POST') Response::error('method_not_allowed', 405);
        $input = Response::input();
        $id = (int)($input['id'] ?? 0);
        if (!$id) Response::error('missing_id');
        $pdo->prepare("UPDATE client_features SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
        Response::success();
        break;

    default:
        Response::error('unknown_action');
}
