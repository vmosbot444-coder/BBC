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
class Response {
    public static function json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function success($data = []) {
        self::json(array_merge(['success' => true], $data));
    }

    public static function error($message, $code = 400) {
        self::json(['success' => false, 'error' => $message], $code);
    }

    public static function param($key, $default = null) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $_POST[$key] ?? $default;
        }
        return $_GET[$key] ?? $default;
    }

    public static function require($key) {
        $val = self::param($key);
        if ($val === null || $val === '') {
            self::error('missing_' . $key);
        }
        return $val;
    }

    public static function sanitize($val) {
        if (is_array($val)) return array_map([self::class, 'sanitize'], $val);
        return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
    }

    public static function input() {
        $json = json_decode(file_get_contents('php://input'), true);
        return $json ?: [];
    }

    public static function timeAgo($datetime) {
        $diff = time() - strtotime($datetime);
        if ($diff < 60) return $diff . 's ago';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
        return date('M j', strtotime($datetime));
    }

    public static function formatBytes($bytes) {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    public static function logActivity($action, $details = []) {
        try {
            $pdo = Database::connect();
            $pdo->prepare("INSERT INTO activity_logs (user_type, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")->execute([
                Auth::userType() ?? 'system',
                Auth::userId(),
                $action,
                json_encode($details),
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        } catch (Exception $e) {}
    }
}
