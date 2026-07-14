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
class Auth {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_name(SESSION_NAME);
            session_start();
        }
    }

    public static function login($userId, $userType, $username) {
        self::start();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_type'] = $userType;
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = time();
    }

    public static function logout() {
        self::start();
        session_destroy();
    }

    public static function check() {
        self::start();
        return isset($_SESSION['user_id']);
    }

    public static function userId() {
        return $_SESSION['user_id'] ?? null;
    }

    public static function userType() {
        return $_SESSION['user_type'] ?? null;
    }

    public static function username() {
        return $_SESSION['username'] ?? null;
    }

    public static function isAdmin() {
        return self::userType() === 'admin';
    }

    public static function isSeller() {
        return self::userType() === 'seller';
    }

    public static function requireAuth() {
        if (!self::check()) {
            Response::error('unauthorized', 401);
        }
    }

    public static function requireAdmin() {
        self::requireAuth();
        if (!self::isAdmin()) {
            Response::error('admin_only', 403);
        }
    }

    public static function requireRole($role) {
        self::requireAuth();
        if (self::userType() !== $role) {
            Response::error('forbidden', 403);
        }
    }
}
