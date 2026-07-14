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
class SellerController {
    public function list() {
        Auth::requireAdmin();
        $pdo = Database::connect();
        $sellers = $pdo->query("SELECT s.*, COUNT(DISTINCT k.id) as key_count, COALESCE(SUM(CASE WHEN t.type = 'refill' THEN t.amount ELSE 0 END), 0) as total_charged, COALESCE(SUM(CASE WHEN t.type = 'payment' THEN t.amount ELSE 0 END), 0) as total_paid FROM sellers s LEFT JOIN `keys` k ON k.created_by_type = 'seller' AND k.created_by_id = s.id LEFT JOIN token_transactions t ON t.seller_id = s.id GROUP BY s.id ORDER BY s.created_at DESC")->fetchAll();
        Response::success(['sellers' => $sellers]);
    }

    public function create() {
        Auth::requireAdmin();
        $pdo = Database::connect();

        $username = Response::sanitize(Response::require('username'));
        $password = Response::require('password');
        $tokens = max(0, (int)Response::param('tokens', 0));

        if (strlen($username) < 3) Response::error('username_too_short');
        if (strlen($password) < 6) Response::error('password_too_short');

        $exists = $pdo->prepare("SELECT id FROM sellers WHERE username = ?");
        $exists->execute([$username]);
        if ($exists->fetch()) Response::error('username_exists');

        $adminExists = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $adminExists->execute([$username]);
        if ($adminExists->fetch()) Response::error('username_exists');

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $stmt = $pdo->prepare("INSERT INTO sellers (username, password, tokens, total_earned, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $tokens, $tokens, Auth::userId()]);
        $sellerId = $pdo->lastInsertId();

        if ($tokens > 0) {
            $amount = (float)Response::param('amount', 0);
            $paidNow = (float)Response::param('paid_now', 0);
            $note = Response::sanitize(Response::param('note', ''));

            $pdo->prepare("INSERT INTO token_transactions (seller_id, type, tokens, amount, note, admin_id) VALUES (?, 'refill', ?, ?, ?, ?)")
                ->execute([$sellerId, $tokens, $amount, $note, Auth::userId()]);

            if ($paidNow > 0) {
                $pdo->prepare("INSERT INTO token_transactions (seller_id, type, amount, note, admin_id) VALUES (?, 'payment', ?, ?, ?)")
                    ->execute([$sellerId, $paidNow, 'initial payment', Auth::userId()]);
            }
        }

        Response::logActivity('seller_created', ['seller' => $username, 'tokens' => $tokens]);
        Response::success(['id' => (int)$sellerId]);
    }

    public function edit() {
        Auth::requireAdmin();
        $pdo = Database::connect();
        $id = (int)Response::require('id');

        $username = Response::param('username');
        $password = Response::param('password');

        if ($username !== null) {
            $username = Response::sanitize($username);
            if (strlen($username) < 3) Response::error('username_too_short');
            $dup = $pdo->prepare("SELECT id FROM sellers WHERE username = ? AND id != ?");
            $dup->execute([$username, $id]);
            if ($dup->fetch()) Response::error('username_exists');
            $pdo->prepare("UPDATE sellers SET username = ? WHERE id = ?")->execute([$username, $id]);
        }

        if ($password !== null && strlen($password) >= 6) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $pdo->prepare("UPDATE sellers SET password = ? WHERE id = ?")->execute([$hash, $id]);
        }

        Response::logActivity('seller_edited', ['seller_id' => $id]);
        Response::success();
    }

    public function toggle() {
        Auth::requireAdmin();
        $id = (int)Response::require('id');
        Database::connect()->prepare("UPDATE sellers SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
        Response::logActivity('seller_toggled', ['seller_id' => $id]);
        Response::success();
    }

    public function delete() {
        Auth::requireAdmin();
        $id = (int)Response::require('id');
        Database::connect()->prepare("DELETE FROM sellers WHERE id = ?")->execute([$id]);
        Response::logActivity('seller_deleted', ['seller_id' => $id]);
        Response::success();
    }

    public function keys() {
        Auth::requireAdmin();
        $id = (int)Response::require('id');
        $stmt = Database::connect()->prepare("SELECT k.*, GROUP_CONCAT(d.hwid SEPARATOR ', ') as hwids FROM `keys` k LEFT JOIN devices d ON d.key_id = k.id WHERE k.created_by_type = 'seller' AND k.created_by_id = ? GROUP BY k.id ORDER BY k.created_at DESC");
        $stmt->execute([$id]);
        Response::success(['keys' => $stmt->fetchAll()]);
    }
}
