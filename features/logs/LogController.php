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
class LogController {
    public function list() {
        Auth::requireAdmin();
        $pdo = Database::connect();

        $page = max(1, (int)Response::param('page', 1));
        $limit = 30;
        $offset = ($page - 1) * $limit;
        $filter = Response::param('filter', '');

        $where = '';
        $params = [];
        if ($filter) {
            $where = "WHERE action LIKE ?";
            $params[] = "%$filter%";
        }

        $total = $pdo->prepare("SELECT COUNT(*) FROM activity_logs $where");
        $total->execute($params);
        $totalCount = $total->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM activity_logs $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        foreach ($logs as &$log) {
            $log['time_ago'] = Response::timeAgo($log['created_at']);
        }

        Response::success([
            'logs' => $logs,
            'total' => (int)$totalCount,
            'page' => $page,
            'pages' => (int)ceil($totalCount / $limit)
        ]);
    }
}
