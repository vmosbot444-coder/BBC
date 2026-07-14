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
class TokenManager {
    public function addTokens() {
        Auth::requireAdmin();
        $pdo = Database::connect();

        $id = (int)Response::require('id');
        $tokens = max(1, (int)Response::require('tokens'));
        $amount = (float)Response::param('amount', 0);
        $note = Response::sanitize(Response::param('note', ''));

        $exists = $pdo->prepare("SELECT id FROM sellers WHERE id = ?");
        $exists->execute([$id]);
        if (!$exists->fetch()) Response::error('seller_not_found');

        $pdo->prepare("UPDATE sellers SET tokens = tokens + ?, total_earned = total_earned + ? WHERE id = ?")->execute([$tokens, $tokens, $id]);

        $pdo->prepare("INSERT INTO token_transactions (seller_id, type, tokens, amount, note, admin_id) VALUES (?, 'refill', ?, ?, ?, ?)")
            ->execute([$id, $tokens, $amount, $note, Auth::userId()]);

        Response::logActivity('tokens_added', ['seller_id' => $id, 'tokens' => $tokens, 'amount' => $amount]);
        Response::success();
    }

    public function removeTokens() {
        Auth::requireAdmin();
        $pdo = Database::connect();

        $id = (int)Response::require('id');
        $tokens = max(1, (int)Response::require('tokens'));
        $note = Response::sanitize(Response::param('note', ''));

        $seller = $pdo->prepare("SELECT tokens FROM sellers WHERE id = ?");
        $seller->execute([$id]);
        $row = $seller->fetch();
        if (!$row) Response::error('seller_not_found');
        if ($row['tokens'] < $tokens) Response::error('not_enough_tokens');

        $pdo->prepare("UPDATE sellers SET tokens = tokens - ? WHERE id = ?")->execute([$tokens, $id]);

        $pdo->prepare("INSERT INTO token_transactions (seller_id, type, tokens, note, admin_id) VALUES (?, 'deduct', ?, ?, ?)")
            ->execute([$id, $tokens, $note, Auth::userId()]);

        Response::logActivity('tokens_removed', ['seller_id' => $id, 'tokens' => $tokens]);
        Response::success();
    }

    public function recordPayment() {
        Auth::requireAdmin();
        $pdo = Database::connect();

        $id = (int)Response::require('id');
        $amount = (float)Response::require('amount');
        $note = Response::sanitize(Response::param('note', ''));

        if ($amount <= 0) Response::error('invalid_amount');

        $exists = $pdo->prepare("SELECT id FROM sellers WHERE id = ?");
        $exists->execute([$id]);
        if (!$exists->fetch()) Response::error('seller_not_found');

        $pdo->prepare("INSERT INTO token_transactions (seller_id, type, amount, note, admin_id) VALUES (?, 'payment', ?, ?, ?)")
            ->execute([$id, $amount, $note, Auth::userId()]);

        Response::logActivity('payment_recorded', ['seller_id' => $id, 'amount' => $amount]);
        Response::success();
    }

    public function transactions() {
        Auth::requireAdmin();
        $id = (int)Response::require('id');
        $pdo = Database::connect();

        $stmt = $pdo->prepare("SELECT t.*, a.username as admin_name FROM token_transactions t LEFT JOIN admins a ON a.id = t.admin_id WHERE t.seller_id = ? ORDER BY t.created_at DESC");
        $stmt->execute([$id]);

        $totals = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN type = 'refill' THEN amount ELSE 0 END), 0) as total_charged, COALESCE(SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END), 0) as total_paid FROM token_transactions WHERE seller_id = ?");
        $totals->execute([$id]);
        $summary = $totals->fetch();

        Response::success([
            'transactions' => $stmt->fetchAll(),
            'total_charged' => (float)$summary['total_charged'],
            'total_paid' => (float)$summary['total_paid']
        ]);
    }
}
