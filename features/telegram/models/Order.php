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
class BotOrder {
    public static function create($botUserId, $planId, $amountPaise, $razorpayOrderId) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO bot_orders (bot_user_id, plan_id, amount_paise, razorpay_order_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$botUserId, $planId, $amountPaise, $razorpayOrderId]);
        return $pdo->lastInsertId();
    }

    public static function markPaid($razorpayOrderId, $razorpayPaymentId, $keyId) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("UPDATE bot_orders SET status = 'paid', razorpay_payment_id = ?, key_id = ?, paid_at = NOW() WHERE razorpay_order_id = ? AND status = 'pending'");
        $stmt->execute([$razorpayPaymentId, $keyId, $razorpayOrderId]);
        return $stmt->rowCount() > 0;
    }

    public static function markFailed($razorpayOrderId) {
        $pdo = Database::connect();
        $pdo->prepare("UPDATE bot_orders SET status = 'failed' WHERE razorpay_order_id = ? AND status = 'pending'")->execute([$razorpayOrderId]);
    }

    public static function getByRazorpayOrder($razorpayOrderId) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT bo.*, bp.name as plan_name, bp.duration_days, bu.telegram_id FROM bot_orders bo JOIN bot_plans bp ON bo.plan_id = bp.id JOIN bot_users bu ON bo.bot_user_id = bu.id WHERE bo.razorpay_order_id = ?");
        $stmt->execute([$razorpayOrderId]);
        return $stmt->fetch();
    }

    public static function getById($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT bo.*, bp.name as plan_name, bp.duration_days, bu.telegram_id, bu.username, k.license_key, k.expires_at FROM bot_orders bo JOIN bot_plans bp ON bo.plan_id = bp.id JOIN bot_users bu ON bo.bot_user_id = bu.id LEFT JOIN `keys` k ON bo.key_id = k.id WHERE bo.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getByUser($telegramId) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT bo.*, bp.name as plan_name, bp.duration_days, bp.price_paise, bp.discount_price_paise, k.license_key, k.status as key_status, k.expires_at, k.device_count FROM bot_orders bo JOIN bot_plans bp ON bo.plan_id = bp.id JOIN bot_users bu ON bo.bot_user_id = bu.id LEFT JOIN `keys` k ON bo.key_id = k.id WHERE bu.telegram_id = ? AND bo.status = 'paid' ORDER BY bo.paid_at DESC");
        $stmt->execute([$telegramId]);
        return $stmt->fetchAll();
    }

    public static function listAll($page = 1, $limit = 50, $status = null, $search = '') {
        $pdo = Database::connect();
        $offset = ($page - 1) * $limit;
        $where = "1=1";
        $params = [];

        if ($status) {
            $where .= " AND bo.status = ?";
            $params[] = $status;
        }
        if ($search) {
            $where .= " AND (bu.username LIKE ? OR bo.razorpay_payment_id LIKE ? OR bo.razorpay_order_id LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM bot_orders bo JOIN bot_users bu ON bo.bot_user_id = bu.id WHERE $where");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $params[] = $limit;
        $params[] = $offset;
        $stmt = $pdo->prepare("SELECT bo.*, bp.name as plan_name, bu.telegram_id, bu.username, bu.first_name, k.license_key FROM bot_orders bo JOIN bot_plans bp ON bo.plan_id = bp.id JOIN bot_users bu ON bo.bot_user_id = bu.id LEFT JOIN `keys` k ON bo.key_id = k.id WHERE $where ORDER BY bo.created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute($params);

        return ['orders' => $stmt->fetchAll(), 'total' => $total];
    }

    public static function analytics() {
        $pdo = Database::connect();
        $stats = [];

        $periods = [
            'today' => "DATE(paid_at) = CURDATE()",
            'week' => "paid_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'month' => "paid_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            'all' => "1=1"
        ];

        foreach ($periods as $key => $cond) {
            $stmt = $pdo->query("SELECT COALESCE(SUM(amount_paise),0) as revenue, COUNT(*) as count FROM bot_orders WHERE status = 'paid' AND $cond");
            $row = $stmt->fetch();
            $stats[$key] = ['revenue' => (int)$row['revenue'], 'count' => (int)$row['count']];
        }

        $popular = $pdo->query("SELECT bp.name, COUNT(*) as cnt FROM bot_orders bo JOIN bot_plans bp ON bo.plan_id = bp.id WHERE bo.status = 'paid' GROUP BY bo.plan_id ORDER BY cnt DESC LIMIT 1")->fetch();
        $stats['popular_plan'] = $popular ? $popular['name'] : 'N/A';

        return $stats;
    }
}
