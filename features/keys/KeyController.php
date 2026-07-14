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
class KeyController {
    public function list() {
        Auth::requireAuth();
        $pdo = Database::connect();

        $page = max(1, (int)Response::param('page', 1));
        $limit = min(100, max(10, (int)Response::param('limit', 20)));
        $offset = ($page - 1) * $limit;
        $status = Response::param('status', '');
        $search = Response::param('search', '');

        $where = [];
        $params = [];

        if (Auth::isSeller()) {
            $where[] = "k.created_by_type = 'seller' AND k.created_by_id = ?";
            $params[] = Auth::userId();
        }

        if ($status) {
            $where[] = "k.status = ?";
            $params[] = $status;
        }

        if ($search) {
            $where[] = "(k.license_key LIKE ? OR k.note LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `keys` k $whereSQL");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT k.*, GROUP_CONCAT(d.hwid SEPARATOR ', ') as hwids FROM `keys` k LEFT JOIN devices d ON d.key_id = k.id $whereSQL GROUP BY k.id ORDER BY k.created_at DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($params);

        Response::success([
            'keys' => $stmt->fetchAll(),
            'total' => (int)$total,
            'page' => $page,
            'pages' => (int)ceil($total / $limit)
        ]);
    }

    public function generate() {
        Auth::requireAuth();
        $pdo = Database::connect();

        $count = min(100, max(1, (int)Response::require('count')));
        $durationDays = (int)Response::require('duration_days');
        $note = Response::sanitize(Response::param('note', ''));
        $maxDevices = (int)Response::param('max_devices', 0);

        if (!$maxDevices) {
            $maxDevices = (int)$pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'default_max_devices'")->fetchColumn();
        }

        if (Auth::isSeller()) {
            $options = json_decode($pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'duration_options'")->fetchColumn(), true);
            $cost = 0;
            foreach ($options as $opt) {
                if ($opt['days'] == $durationDays) { $cost = $opt['token_cost']; break; }
            }
            if ($cost <= 0) Response::error('invalid_duration');

            $totalCost = $cost * $count;
            $seller = $pdo->prepare("SELECT tokens FROM sellers WHERE id = ?");
            $seller->execute([Auth::userId()]);
            $tokens = (int)$seller->fetchColumn();

            if ($tokens < $totalCost) Response::error('insufficient_tokens');

            $pdo->prepare("UPDATE sellers SET tokens = tokens - ?, total_spent = total_spent + ? WHERE id = ?")->execute([$totalCost, $totalCost, Auth::userId()]);
            $pdo->prepare("INSERT INTO token_transactions (seller_id, type, tokens, admin_id) VALUES (?, 'spend', ?, NULL)")->execute([Auth::userId(), $totalCost]);
        }

        $generated = [];
        $byType = Auth::isAdmin() ? 'admin' : 'seller';
        $byId = Auth::userId();

        for ($i = 0; $i < $count; $i++) {
            $key = KeyGenerator::generate();
            $pdo->prepare("INSERT INTO `keys` (license_key, duration_days, max_devices, created_by_type, created_by_id, note) VALUES (?, ?, ?, ?, ?, ?)")->execute([$key, $durationDays, $maxDevices, $byType, $byId, $note]);
            $generated[] = $key;
        }

        Response::logActivity('keys_generated', ['count' => $count, 'duration' => $durationDays]);
        Response::success(['keys' => $generated, 'count' => $count]);
    }

    public function edit() {
        Auth::requireAuth();
        $pdo = Database::connect();
        $id = (int)Response::require('id');

        if (Auth::isSeller()) {
            $check = $pdo->prepare("SELECT id FROM `keys` WHERE id = ? AND created_by_type = 'seller' AND created_by_id = ?");
            $check->execute([$id, Auth::userId()]);
            if (!$check->fetch()) Response::error('not_your_key');

            $note = Response::param('note');
            if ($note !== null) {
                $pdo->prepare("UPDATE `keys` SET note = ? WHERE id = ?")->execute([Response::sanitize($note), $id]);
            }
        } else {
            $updates = [];
            $params = [];
            foreach (['note', 'status', 'duration_days', 'max_devices'] as $f) {
                $val = Response::param($f);
                if ($val !== null) {
                    $updates[] = "$f = ?";
                    $params[] = $f === 'note' ? Response::sanitize($val) : $val;
                }
            }
            if (empty($updates)) Response::error('nothing_to_update');
            $params[] = $id;
            $pdo->prepare("UPDATE `keys` SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
        }

        Response::logActivity('key_edited', ['key_id' => $id]);
        Response::success();
    }

    public function resetDevice() {
        Auth::requireAuth();
        $id = (int)Response::require('id');

        if (Auth::isSeller()) {
            $pdo = Database::connect();
            $check = $pdo->prepare("SELECT id FROM `keys` WHERE id = ? AND created_by_type = 'seller' AND created_by_id = ?");
            $check->execute([$id, Auth::userId()]);
            if (!$check->fetch()) Response::error('not_your_key');
        }

        DeviceManager::resetForKey($id);
        Response::logActivity('device_reset', ['key_id' => $id]);
        Response::success();
    }

    public function resetAll() {
        Auth::requireAdmin();
        DeviceManager::resetAll();
        Response::logActivity('all_devices_reset');
        Response::success();
    }

    public function deleteExpired() {
        Auth::requireAdmin();
        $pdo = Database::connect();
        $count = (int)$pdo->query("SELECT COUNT(*) FROM `keys` WHERE status = 'expired'")->fetchColumn();
        $pdo->exec("DELETE FROM `keys` WHERE status = 'expired'");
        Response::logActivity('expired_keys_deleted', ['count' => $count]);
        Response::success(['deleted' => $count]);
    }

    public function ban() {
        Auth::requireAdmin();
        $id = (int)Response::require('id');
        Database::connect()->prepare("UPDATE `keys` SET status = 'banned' WHERE id = ?")->execute([$id]);
        Response::logActivity('key_banned', ['key_id' => $id]);
        Response::success();
    }

    public function unban() {
        Auth::requireAdmin();
        $id = (int)Response::require('id');
        Database::connect()->prepare("UPDATE `keys` SET status = 'active' WHERE id = ?")->execute([$id]);
        Response::logActivity('key_unbanned', ['key_id' => $id]);
        Response::success();
    }

    public function delete() {
        Auth::requireAdmin();
        $id = (int)Response::require('id');
        Database::connect()->prepare("DELETE FROM `keys` WHERE id = ?")->execute([$id]);
        Response::logActivity('key_deleted', ['key_id' => $id]);
        Response::success();
    }
    public function addDays() {
        Auth::requireAdmin();
        $pdo = Database::connect();

        $days = max(1, (int)Response::require('days'));
        $filter = Response::param('filter', 'active');

        $where = "status = 'active' AND expires_at IS NOT NULL";
        if ($filter === 'all') {
            $where = "expires_at IS NOT NULL AND status IN ('active', 'unused')";
        }

        $stmt = $pdo->prepare("UPDATE `keys` SET expires_at = DATE_ADD(expires_at, INTERVAL ? DAY) WHERE $where");
        $stmt->execute([$days]);
        $affected = $stmt->rowCount();

        Response::logActivity('bulk_days_added', ['days' => $days, 'filter' => $filter, 'keys_affected' => $affected]);
        Response::success(['affected' => $affected]);
    }
}
