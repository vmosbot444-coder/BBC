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
require_once __DIR__ . '/../models/BotUser.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Plan.php';
require_once __DIR__ . '/../models/BotMessage.php';
require_once __DIR__ . '/../bot/TelegramAPI.php';
require_once __DIR__ . '/../payments/RazorpayClient.php';
require_once __DIR__ . '/../payments/OrderFulfiller.php';
require_once __DIR__ . '/../../keys/KeyGenerator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('method_not_allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$razorpayOrderId = $input['razorpay_order_id'] ?? '';
$razorpayPaymentId = $input['razorpay_payment_id'] ?? '';
$razorpaySignature = $input['razorpay_signature'] ?? '';

if (!$razorpayOrderId || !$razorpayPaymentId || !$razorpaySignature) {
    Response::error('missing_payment_data');
}

$config = BotConfig::get();
if (!$config['rp_key_secret']) {
    Response::error('razorpay_not_configured');
}

$expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $config['rp_key_secret']);

if (!hash_equals($expectedSignature, $razorpaySignature)) {
    Response::error('invalid_signature');
}

$result = OrderFulfiller::fulfill($razorpayOrderId, $razorpayPaymentId);

if ($result['success']) {
    Response::success(['key' => $result['key'] ?? '']);
} else {
    Response::error($result['error'] ?? 'fulfillment_failed');
}
