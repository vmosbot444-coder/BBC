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
class KeyReset {
    public static function canReset($keyId, $telegramId) {
        $limits = BotConfig::getResetLimits();
        $pdo = Database::connect();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bot_key_resets WHERE key_id = ? AND DATE(reset_at) = CURDATE()");
        $stmt->execute([$keyId]);
        $dailyCount = (int)$stmt->fetchColumn();

        if ($dailyCount >= $limits['daily_limit']) {
            return ['allowed' => false, 'reason' => 'daily_limit', 'resets_today' => $dailyCount, 'limit' => $limits['daily_limit']];
        }

        $stmt = $pdo->prepare("SELECT reset_at FROM bot_key_resets WHERE key_id = ? ORDER BY reset_at DESC LIMIT 1");
        $stmt->execute([$keyId]);
        $last = $stmt->fetch();

        if ($last) {
            $lastTime = strtotime($last['reset_at']);
            $cooldownEnd = $lastTime + ($limits['cooldown_minutes'] * 60);
            $now = time();
            if ($now < $cooldownEnd) {
                $waitMinutes = ceil(($cooldownEnd - $now) / 60);
                return ['allowed' => false, 'reason' => 'cooldown', 'wait_minutes' => $waitMinutes];
            }
        }

        return ['allowed' => true, 'resets_today' => $dailyCount, 'limit' => $limits['daily_limit']];
    }

    public static function recordReset($keyId, $telegramId) {
        $pdo = Database::connect();
        $pdo->prepare("INSERT INTO bot_key_resets (key_id, telegram_id) VALUES (?, ?)")->execute([$keyId, $telegramId]);
    }

    public static function performReset($keyId, $telegramId) {
        $check = self::canReset($keyId, $telegramId);
        if (!$check['allowed']) return $check;

        $pdo = Database::connect();
        $pdo->prepare("DELETE FROM devices WHERE key_id = ?")->execute([$keyId]);
        $pdo->prepare("UPDATE `keys` SET device_count = 0 WHERE id = ?")->execute([$keyId]);
        self::recordReset($keyId, $telegramId);

        $limits = BotConfig::getResetLimits();
        return [
            'allowed' => true,
            'success' => true,
            'resets_today' => $check['resets_today'] + 1,
            'limit' => $limits['daily_limit']
        ];
    }

    public static function getResetsToday($keyId) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bot_key_resets WHERE key_id = ? AND DATE(reset_at) = CURDATE()");
        $stmt->execute([$keyId]);
        return (int)$stmt->fetchColumn();
    }

    public static function todayTotalResets() {
        return (int)Database::connect()->query("SELECT COUNT(*) FROM bot_key_resets WHERE DATE(reset_at) = CURDATE()")->fetchColumn();
    }
}
