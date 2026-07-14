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
class ClientAuthController {
    public function handle() {
        RateLimiter::check('client_auth');

        $input = Response::input();
        $key = Response::sanitize($input['key'] ?? '');
        $hwid = Response::sanitize($input['hwid'] ?? '');
        $deviceInfo = Response::sanitize($input['device_info'] ?? '');
        $arch = Response::sanitize($input['arch'] ?? '');
        $apkHash = Response::sanitize($input['apk_hash'] ?? '');
        $libHash = Response::sanitize($input['lib_hash'] ?? '');

        if (!$key || !$hwid) Response::error('missing_key_or_hwid');

        $pdo = Database::connect();

        $maint = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'")->fetchColumn();
        if ($maint === 'true') {
            $msg = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_message'")->fetchColumn();
            Response::error($msg ?: 'maintenance', 503);
        }

        $expectedApk = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'expected_apk_hash'")->fetchColumn();
        $expectedLib = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'expected_lib_hash'")->fetchColumn();

        if ($expectedApk && $apkHash !== $expectedApk) Response::error('tampered_client');
        if ($expectedLib && $libHash !== $expectedLib) Response::error('tampered_client');

        $banned = $pdo->prepare("SELECT hwid FROM hwid_bans WHERE hwid = ?");
        $banned->execute([$hwid]);
        if ($banned->fetch()) Response::error('device_banned');

        $stmt = $pdo->prepare("SELECT * FROM `keys` WHERE license_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        if (!$row) Response::error('invalid_key');
        if ($row['status'] === 'banned') Response::error('key_banned');
        if ($row['status'] === 'expired') Response::error('key_expired');

        if ($row['status'] === 'unused') {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $row['duration_days'] . ' days'));
            $pdo->prepare("UPDATE `keys` SET status = 'active', activated_at = NOW(), expires_at = ? WHERE id = ?")->execute([$expiresAt, $row['id']]);
            $row['status'] = 'active';
            $row['expires_at'] = $expiresAt;
        }

        if ($row['expires_at'] && strtotime($row['expires_at']) < time()) {
            $pdo->prepare("UPDATE `keys` SET status = 'expired' WHERE id = ?")->execute([$row['id']]);
            Response::error('key_expired');
        }

        $deviceStmt = $pdo->prepare("SELECT * FROM devices WHERE key_id = ? AND hwid = ?");
        $deviceStmt->execute([$row['id'], $hwid]);

        if (!$deviceStmt->fetch()) {
            if ($row['device_count'] >= $row['max_devices']) {
                Response::error('device_limit_reached');
            }
            $pdo->prepare("INSERT INTO devices (key_id, hwid, device_info) VALUES (?, ?, ?)")->execute([$row['id'], $hwid, $deviceInfo]);
            $pdo->prepare("UPDATE `keys` SET device_count = device_count + 1 WHERE id = ?")->execute([$row['id']]);
        }

        $sellerName = '';
        if ($row['created_by_type'] === 'seller') {
            $sellerStmt = $pdo->prepare("SELECT username FROM sellers WHERE id = ?");
            $sellerStmt->execute([$row['created_by_id']]);
            $sellerName = $sellerStmt->fetchColumn() ?: '';
        } elseif ($row['created_by_type'] === 'admin') {
            $adminStmt = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
            $adminStmt->execute([$row['created_by_id']]);
            $sellerName = $adminStmt->fetchColumn() ?: '';
        }

        $sessionToken = bin2hex(random_bytes(32));
        $downloadToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 300);

        $pdo->prepare("DELETE FROM client_sessions WHERE key_id = ? AND status = 'created'")->execute([$row['id']]);

        $pdo->prepare("INSERT INTO client_sessions (session_token, download_token, key_id, hwid, arch, expires_at) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$sessionToken, $downloadToken, $row['id'], $hwid, $arch ?: 'arm64-v8a', $expiresAt]);

        $fileUrl = null;
        $version = null;
        $fileArch = $arch ?: 'arm64-v8a';
        $fileStmt = $pdo->prepare("SELECT filename, version FROM files WHERE arch = ? AND is_active = 1 ORDER BY uploaded_at DESC LIMIT 1");
        $fileStmt->execute([$fileArch]);
        $file = $fileStmt->fetch();
        if ($file) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $fileUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/api/client/download?token=' . $downloadToken;
            $version = $file['version'];
        }

        $announcement = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'announcement'")->fetchColumn();

        Response::logActivity('auth_success', ['key' => $key, 'hwid' => $hwid, 'arch' => $arch]);

        Response::success([
            'session_token' => $sessionToken,
            'file_url' => $fileUrl,
            'expires_at' => $row['expires_at'],
            'version' => $version,
            'announcement' => $announcement ?: null,
            'seller' => $sellerName
        ]);
    }
}
