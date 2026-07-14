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
class SetupController {
    public function status() {
        $dbTest = Database::testConnection();
        $installed = false;

        if ($dbTest['success']) {
            try {
                $pdo = Database::connect();
                $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
                $installed = $stmt->fetchColumn() > 0;
            } catch (Exception $e) {}
        }

        Response::success([
            'db_connected' => $dbTest['success'],
            'db_error' => $dbTest['error'] ?? null,
            'installed' => $installed,
            'app_name' => APP_NAME,
            'app_version' => APP_VERSION
        ]);
    }

    public function testDb() {
        $result = Database::testConnection();
        if ($result['success']) {
            Response::success(['message' => 'Database connection successful']);
        } else {
            Response::error($result['error']);
        }
    }

    public function install() {
        $username = Response::sanitize(Response::require('username'));
        $password = Response::require('password');
        $confirmPassword = Response::require('confirm_password');
        $appVersion = Response::sanitize(Response::param('app_version', APP_VERSION));
        $maxDevices = (int)Response::param('max_devices', DEFAULT_MAX_DEVICES);

        if (strlen($username) < 3) Response::error('username_min_3_chars');
        if (strlen($password) < 6) Response::error('password_min_6_chars');
        if ($password !== $confirmPassword) Response::error('passwords_dont_match');

        $pdo = Database::connect();

        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
            if ($stmt->fetchColumn() > 0) {
                Response::error('already_installed');
            }
        } catch (Exception $e) {}

        foreach (Schema::getStatements() as $sql) {
            $pdo->exec($sql);
        }

        require_once __DIR__ . '/../telegram/Schema.php';
        foreach (TelegramSchema::getStatements() as $sql) {
            $pdo->exec($sql);
        }
        TelegramSchema::seedConfig($pdo);

        foreach (Schema::getDefaultSettings() as [$key, $value]) {
            if ($key === 'default_max_devices') $value = (string)$maxDevices;
            if ($key === 'app_version') $value = $appVersion;
            $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)")->execute([$key, $value]);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)")->execute([$username, $hash]);

        Response::success(['message' => 'Installation complete']);
    }
}
