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
class BotUser {
    public static function findOrCreate($telegramId, $userData = []) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM bot_users WHERE telegram_id = ?");
        $stmt->execute([$telegramId]);
        $user = $stmt->fetch();

        if ($user) {
            $pdo->prepare("UPDATE bot_users SET username = ?, first_name = ?, last_name = ?, last_active = NOW() WHERE id = ?")
                ->execute([
                    $userData['username'] ?? $user['username'],
                    $userData['first_name'] ?? $user['first_name'],
                    $userData['last_name'] ?? $user['last_name'],
                    $user['id']
                ]);
            return $user;
        }

        $pdo->prepare("INSERT INTO bot_users (telegram_id, username, first_name, last_name) VALUES (?, ?, ?, ?)")
            ->execute([
                $telegramId,
                $userData['username'] ?? '',
                $userData['first_name'] ?? '',
                $userData['last_name'] ?? ''
            ]);
        return self::getByTelegramId($telegramId);
    }

    public static function getByTelegramId($telegramId) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM bot_users WHERE telegram_id = ?");
        $stmt->execute([$telegramId]);
        return $stmt->fetch();
    }

    public static function getById($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM bot_users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function listAll($page = 1, $limit = 50) {
        $pdo = Database::connect();
        $offset = ($page - 1) * $limit;
        $total = $pdo->query("SELECT COUNT(*) FROM bot_users")->fetchColumn();
        $stmt = $pdo->prepare("SELECT bu.*, (SELECT COUNT(*) FROM bot_orders WHERE bot_user_id = bu.id AND status = 'paid') as order_count FROM bot_users bu ORDER BY bu.last_active DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        return ['users' => $stmt->fetchAll(), 'total' => $total];
    }

    public static function getAllIds() {
        $pdo = Database::connect();
        return $pdo->query("SELECT telegram_id FROM bot_users")->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function count() {
        return Database::connect()->query("SELECT COUNT(*) FROM bot_users")->fetchColumn();
    }
}
