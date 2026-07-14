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
class DeviceManager {
    public static function resetForKey($keyId) {
        $pdo = Database::connect();
        $pdo->prepare("DELETE FROM devices WHERE key_id = ?")->execute([$keyId]);
        $pdo->prepare("UPDATE `keys` SET device_count = 0 WHERE id = ?")->execute([$keyId]);
    }

    public static function resetAll() {
        $pdo = Database::connect();
        $pdo->exec("DELETE FROM devices");
        $pdo->exec("UPDATE `keys` SET device_count = 0");
    }

    public static function banHwid($hwid, $reason, $bannedBy) {
        $pdo = Database::connect();
        $pdo->prepare("INSERT IGNORE INTO hwid_bans (hwid, reason, banned_by) VALUES (?, ?, ?)")->execute([$hwid, $reason, $bannedBy]);
    }

    public static function unbanHwid($hwid) {
        $pdo = Database::connect();
        $pdo->prepare("DELETE FROM hwid_bans WHERE hwid = ?")->execute([$hwid]);
    }
}
