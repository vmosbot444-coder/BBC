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
class OrderFulfiller {
    public static function fulfill($razorpayOrderId, $razorpayPaymentId) {
        $order = BotOrder::getByRazorpayOrder($razorpayOrderId);
        if (!$order) return ['success' => false, 'error' => 'Order not found'];
        if ($order['status'] === 'paid') return ['success' => true, 'already' => true];

        $pdo = Database::connect();
        $key = KeyGenerator::generate();
        $duration = (int)$order['duration_days'];

        $stmt = $pdo->prepare("INSERT INTO `keys` (license_key, status, duration_days, max_devices, created_by_type, created_by_id, note) VALUES (?, 'unused', ?, ?, 'admin', 1, ?)");
        $stmt->execute([$key, $duration, DEFAULT_MAX_DEVICES, 'Bot purchase #' . $order['id']]);
        $keyId = $pdo->lastInsertId();

        BotOrder::markPaid($razorpayOrderId, $razorpayPaymentId, $keyId);

        $expiry = date('M j, Y', strtotime("+{$duration} days"));
        $plan = Plan::getById($order['plan_id']);
        $planName = $plan ? $plan['name'] : "{$duration}-Day Key";

        $config = BotConfig::get();
        if ($config && $config['bot_token'] && $order['telegram_id']) {
            $api = new TelegramAPI($config['bot_token']);

            $msg = BotMessage::format('key_delivery', [
                'key' => $key,
                'plan' => $planName,
                'expiry' => $expiry
            ]);

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = "$protocol://$host/features/telegram/webapp";

            $api->sendMessageWithInline($order['telegram_id'], $msg, [
                [
                    ['text' => '🧾 View Receipt', 'web_app' => "$baseUrl/receipt.php?order_id={$order['id']}&tid={$order['telegram_id']}"],
                    ['text' => '🛒 Buy Another', 'web_app' => "$baseUrl/store.php?tid={$order['telegram_id']}"]
                ]
            ]);
        }

        try {
            Response::logActivity('bot_purchase', [
                'order_id' => $order['id'],
                'key' => $key,
                'plan' => $planName,
                'amount' => $order['amount_paise'] / 100
            ]);
        } catch (Exception $e) {}

        return ['success' => true, 'key' => $key, 'key_id' => $keyId];
    }
}
