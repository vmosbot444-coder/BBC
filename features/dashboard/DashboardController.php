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
class DashboardController {
    public function index() {
        Auth::requireAuth();
        $pdo = Database::connect();

        if (Auth::isAdmin()) {
            $totalKeys = (int)$pdo->query("SELECT COUNT(*) FROM `keys`")->fetchColumn();
            $activeKeys = (int)$pdo->query("SELECT COUNT(*) FROM `keys` WHERE status = 'active'")->fetchColumn();
            $expiredKeys = (int)$pdo->query("SELECT COUNT(*) FROM `keys` WHERE status = 'expired'")->fetchColumn();
            $unusedKeys = (int)$pdo->query("SELECT COUNT(*) FROM `keys` WHERE status = 'unused'")->fetchColumn();
            $bannedKeys = (int)$pdo->query("SELECT COUNT(*) FROM `keys` WHERE status = 'banned'")->fetchColumn();
            $totalSellers = (int)$pdo->query("SELECT COUNT(*) FROM sellers")->fetchColumn();
            $activeSellers = (int)$pdo->query("SELECT COUNT(*) FROM sellers WHERE is_active = 1")->fetchColumn();
            $totalDevices = (int)$pdo->query("SELECT COUNT(*) FROM devices")->fetchColumn();

            $revenue = self::getRevenue($pdo);

            $keysPerDay = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM `keys` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date")->fetchAll();

            $topSellers = $pdo->query("SELECT s.username, COUNT(k.id) as key_count, s.tokens FROM sellers s LEFT JOIN `keys` k ON k.created_by_type = 'seller' AND k.created_by_id = s.id GROUP BY s.id ORDER BY key_count DESC LIMIT 5")->fetchAll();

            $recentLogs = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 15")->fetchAll();
            foreach ($recentLogs as &$log) {
                $log['time_ago'] = Response::timeAgo($log['created_at']);
            }

            Response::success([
                'role' => 'admin',
                'username' => Auth::username(),
                'total_keys' => $totalKeys,
                'active_keys' => $activeKeys,
                'expired_keys' => $expiredKeys,
                'unused_keys' => $unusedKeys,
                'banned_keys' => $bannedKeys,
                'total_sellers' => $totalSellers,
                'active_sellers' => $activeSellers,
                'total_devices' => $totalDevices,
                'revenue' => $revenue,
                'keys_per_day' => $keysPerDay,
                'top_sellers' => $topSellers,
                'recent_logs' => $recentLogs,
                'maintenance' => $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'")->fetchColumn() === 'true'
            ]);
        }

        if (Auth::isSeller()) {
            $sid = Auth::userId();
            $seller = $pdo->prepare("SELECT tokens, total_earned, total_spent FROM sellers WHERE id = ?");
            $seller->execute([$sid]);
            $s = $seller->fetch();

            $myKeysStmt = $pdo->prepare("SELECT COUNT(*) FROM `keys` WHERE created_by_type = 'seller' AND created_by_id = ?");
            $myKeysStmt->execute([$sid]);
            $totalKeys = (int)$myKeysStmt->fetchColumn();

            $activeStmt = $pdo->prepare("SELECT COUNT(*) FROM `keys` WHERE created_by_type = 'seller' AND created_by_id = ? AND status = 'active'");
            $activeStmt->execute([$sid]);
            $activeKeys = (int)$activeStmt->fetchColumn();

            $unusedStmt = $pdo->prepare("SELECT COUNT(*) FROM `keys` WHERE created_by_type = 'seller' AND created_by_id = ? AND status = 'unused'");
            $unusedStmt->execute([$sid]);
            $unusedKeys = (int)$unusedStmt->fetchColumn();

            $expiredStmt = $pdo->prepare("SELECT COUNT(*) FROM `keys` WHERE created_by_type = 'seller' AND created_by_id = ? AND status = 'expired'");
            $expiredStmt->execute([$sid]);
            $expiredKeys = (int)$expiredStmt->fetchColumn();

            $keysPerDay = $pdo->prepare("SELECT DATE(created_at) as date, COUNT(*) as count FROM `keys` WHERE created_by_type = 'seller' AND created_by_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date");
            $keysPerDay->execute([$sid]);

            $recentKeys = $pdo->prepare("SELECT license_key, status, duration_days, created_at FROM `keys` WHERE created_by_type = 'seller' AND created_by_id = ? ORDER BY created_at DESC LIMIT 8");
            $recentKeys->execute([$sid]);
            $recentKeysList = $recentKeys->fetchAll();
            foreach ($recentKeysList as &$rk) {
                $rk['time_ago'] = Response::timeAgo($rk['created_at']);
            }

            $recentTx = $pdo->prepare("SELECT type, tokens, created_at FROM token_transactions WHERE seller_id = ? ORDER BY created_at DESC LIMIT 8");
            $recentTx->execute([$sid]);
            $txList = $recentTx->fetchAll();
            foreach ($txList as &$tx) {
                $tx['time_ago'] = Response::timeAgo($tx['created_at']);
            }

            Response::success([
                'role' => 'seller',
                'username' => Auth::username(),
                'tokens' => (int)$s['tokens'],
                'total_earned' => (int)$s['total_earned'],
                'total_spent' => (int)$s['total_spent'],
                'total_keys' => $totalKeys,
                'active_keys' => $activeKeys,
                'unused_keys' => $unusedKeys,
                'expired_keys' => $expiredKeys,
                'keys_per_day' => $keysPerDay->fetchAll(),
                'recent_keys' => $recentKeysList,
                'recent_transactions' => $txList
            ]);
        }
    }

    private static function getRevenue($pdo) {
        $periods = [
            'today' => 'DATE(created_at) = CURDATE()',
            '7d' => 'created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)',
            '30d' => 'created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
            'all' => '1=1'
        ];

        $result = [];
        foreach ($periods as $key => $where) {
            $stmt = $pdo->query("SELECT COALESCE(SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END), 0) as collected, COALESCE(SUM(CASE WHEN type = 'refill' THEN amount ELSE 0 END), 0) as charged FROM token_transactions WHERE $where");
            $row = $stmt->fetch();
            $result[$key] = [
                'collected' => (float)$row['collected'],
                'due' => (float)$row['charged'] - (float)$row['collected']
            ];
        }

        return $result;
    }
}
