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
class BotConfig {
    public static function get() {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT * FROM bot_config WHERE id = 1");
        $row = $stmt->fetch();
        if (!$row) {
            $pdo->exec("INSERT INTO bot_config (id) VALUES (1)");
            return self::get();
        }
        return $row;
    }

    public static function update($data) {
        $pdo = Database::connect();
        $allowed = ['bot_token','bot_username','bot_name','rp_key_id','rp_key_secret',
                     'rp_webhook_secret','rp_mode','is_active','reset_limit_per_day','reset_cooldown_minutes',
                     'apk_download_url','setup_video_url'];
        $sets = [];
        $vals = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $allowed)) {
                $sets[] = "$k = ?";
                $vals[] = $v;
            }
        }
        if (empty($sets)) return false;
        $vals[] = 1;
        $pdo->prepare("UPDATE bot_config SET " . implode(', ', $sets) . " WHERE id = ?")->execute($vals);
        return true;
    }

    public static function getToken() {
        $cfg = self::get();
        return $cfg['bot_token'] ?? '';
    }

    public static function getRazorpay() {
        $cfg = self::get();
        return [
            'key_id' => $cfg['rp_key_id'] ?? '',
            'key_secret' => $cfg['rp_key_secret'] ?? '',
            'webhook_secret' => $cfg['rp_webhook_secret'] ?? '',
            'mode' => $cfg['rp_mode'] ?? 'test'
        ];
    }

    public static function getResetLimits() {
        $cfg = self::get();
        return [
            'daily_limit' => (int)($cfg['reset_limit_per_day'] ?? 3),
            'cooldown_minutes' => (int)($cfg['reset_cooldown_minutes'] ?? 30)
        ];
    }
}
