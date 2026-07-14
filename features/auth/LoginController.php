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
class LoginController {
    public function login() {
        RateLimiter::check('login');

        $username = Response::sanitize(Response::require('username'));
        $password = Response::require('password');

        $pdo = Database::connect();

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            Auth::login($admin['id'], 'admin', $admin['username']);
            Response::logActivity('login', ['ip' => $_SERVER['REMOTE_ADDR']]);
            Response::success(['role' => 'admin', 'username' => $admin['username']]);
        }

        $stmt = $pdo->prepare("SELECT * FROM sellers WHERE username = ?");
        $stmt->execute([$username]);
        $seller = $stmt->fetch();

        if ($seller && password_verify($password, $seller['password'])) {
            if (!$seller['is_active']) Response::error('account_disabled');
            Auth::login($seller['id'], 'seller', $seller['username']);
            $pdo->prepare("UPDATE sellers SET last_login = NOW() WHERE id = ?")->execute([$seller['id']]);
            Response::logActivity('login', ['ip' => $_SERVER['REMOTE_ADDR']]);
            Response::success(['role' => 'seller', 'username' => $seller['username'], 'tokens' => $seller['tokens']]);
        }

        Response::error('invalid_credentials');
    }

    public function logout() {
        Auth::logout();
        Response::success();
    }

    public function updateAccount() {
        Auth::requireAuth();
        $pdo = Database::connect();

        $currentPassword = Response::require('current_password');
        $newPassword = Response::param('new_password', '');
        $newUsername = Response::param('new_username', '');

        $table = Auth::isAdmin() ? 'admins' : 'sellers';
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->execute([Auth::userId()]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            Response::error('wrong_password');
        }

        if ($newUsername && Auth::isAdmin()) {
            $newUsername = Response::sanitize($newUsername);
            if (strlen($newUsername) < 3) Response::error('username_too_short');
            $check = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
            $check->execute([$newUsername, Auth::userId()]);
            if ($check->fetch()) Response::error('username_taken');
            $pdo->prepare("UPDATE admins SET username = ? WHERE id = ?")->execute([$newUsername, Auth::userId()]);
            $_SESSION['username'] = $newUsername;
        }

        if ($newPassword) {
            if (strlen($newPassword) < 6) Response::error('password_too_short');
            $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $pdo->prepare("UPDATE $table SET password = ? WHERE id = ?")->execute([$hash, Auth::userId()]);
        }

        Response::logActivity('account_updated');
        Response::success();
    }
}
