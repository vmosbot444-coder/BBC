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
class RateLimiter {
    private static $pdo;

    private static function db() {
        if (!self::$pdo) self::$pdo = Database::connect();
        return self::$pdo;
    }

    public static function check($endpoint) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        try {
            $pdo = self::db();

            $banned = $pdo->prepare("SELECT id FROM rate_limits WHERE ip_address = ? AND is_blocked = 1 AND blocked_until > NOW()");
            $banned->execute([$ip]);
            if ($banned->fetch()) {
                Response::error('rate_limited', 429);
            }

            $pdo->prepare("DELETE FROM rate_limits WHERE is_blocked = 0 AND created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)")->execute([RATE_LIMIT_WINDOW]);

            $count = $pdo->prepare("SELECT COUNT(*) FROM rate_limits WHERE ip_address = ? AND endpoint = ? AND is_blocked = 0");
            $count->execute([$ip, $endpoint]);
            $hits = (int)$count->fetchColumn();

            $pdo->prepare("INSERT INTO rate_limits (ip_address, endpoint) VALUES (?, ?)")->execute([$ip, $endpoint]);

            if ($hits >= RATE_LIMIT_REQUESTS) {
                $violations = $pdo->prepare("SELECT COUNT(DISTINCT endpoint) FROM rate_limits WHERE ip_address = ? AND is_blocked = 0");
                $violations->execute([$ip]);

                if ((int)$violations->fetchColumn() >= RATE_LIMIT_BAN_THRESHOLD) {
                    $pdo->prepare("INSERT INTO rate_limits (ip_address, endpoint, is_blocked, blocked_until) VALUES (?, 'ban', 1, DATE_ADD(NOW(), INTERVAL ? SECOND))")->execute([$ip, RATE_LIMIT_BAN_DURATION]);
                }

                Response::error('rate_limited', 429);
            }
        } catch (PDOException $e) {}
    }
}
