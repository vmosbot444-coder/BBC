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
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../models/BotConfig.php';
require_once __DIR__ . '/../models/Plan.php';
require_once __DIR__ . '/../models/BotUser.php';
require_once __DIR__ . '/../models/BotMessage.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../bot/TelegramAPI.php';
require_once __DIR__ . '/../../../features/keys/KeyGenerator.php';
require_once __DIR__ . '/RazorpayClient.php';
require_once __DIR__ . '/OrderFulfiller.php';

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if (!$payload || !$signature) {
    http_response_code(400);
    exit;
}

$rp = new RazorpayClient();
if (!$rp->verifyWebhookSignature($payload, $signature)) {
    http_response_code(401);
    exit;
}

$event = json_decode($payload, true);
$eventType = $event['event'] ?? '';

if ($eventType === 'payment.captured' || $eventType === 'payment.authorized') {
    $payment = $event['payload']['payment']['entity'] ?? [];
    $orderId = $payment['order_id'] ?? '';
    $paymentId = $payment['id'] ?? '';

    if ($orderId && $paymentId) {
        try {
            OrderFulfiller::fulfill($orderId, $paymentId);
        } catch (Exception $e) {
            error_log("Razorpay webhook fulfill error: " . $e->getMessage());
        }
    }
} elseif ($eventType === 'payment.failed') {
    $payment = $event['payload']['payment']['entity'] ?? [];
    $orderId = $payment['order_id'] ?? '';
    if ($orderId) {
        try {
            BotOrder::markFailed($orderId);
        } catch (Exception $e) {
            error_log("Razorpay webhook fail error: " . $e->getMessage());
        }
    }
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
