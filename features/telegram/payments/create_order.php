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
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/RazorpayClient.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { Response::error('method_not_allowed', 405); }

$input = json_decode(file_get_contents('php://input'), true);
$planId = $input['plan_id'] ?? null;
$telegramId = $input['telegram_id'] ?? null;

if (!$planId || !$telegramId) Response::error('missing_params');

$plan = Plan::getById($planId);
if (!$plan || !$plan['is_active']) Response::error('invalid_plan');

$user = BotUser::getByTelegramId($telegramId);
if (!$user) Response::error('user_not_found');

$amount = Plan::getEffectivePrice($plan);

$pdo = Database::connect();
$stmt = $pdo->prepare("SELECT razorpay_order_id FROM bot_orders WHERE bot_user_id = ? AND plan_id = ? AND status = 'pending' AND amount_paise = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE) ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user['id'], $planId, $amount]);
$existing = $stmt->fetch();

$rp = new RazorpayClient();
$rpOrderId = null;

if ($existing && $existing['razorpay_order_id']) {
    $rpStatus = $rp->fetchOrder($existing['razorpay_order_id']);
    if (isset($rpStatus['status']) && $rpStatus['status'] === 'created') {
        $rpOrderId = $existing['razorpay_order_id'];
    } else {
        $pdo->prepare("UPDATE bot_orders SET status = 'failed' WHERE razorpay_order_id = ? AND status = 'pending'")->execute([$existing['razorpay_order_id']]);
    }
}

if (!$rpOrderId) {
    $rpOrder = $rp->createOrder($amount, 'INR', 'bot_' . $user['id'] . '_' . time(), [
        'plan_id' => (string)$planId,
        'telegram_id' => (string)$telegramId,
        'bot_user_id' => (string)$user['id']
    ]);

    if (isset($rpOrder['error'])) {
        Response::error('razorpay_error: ' . $rpOrder['error']);
    }

    $rpOrderId = $rpOrder['id'];
    BotOrder::create($user['id'], $planId, $amount, $rpOrderId);
}

$config = $rp->getCheckoutConfig($rpOrderId, $amount, $plan['name']);

Response::success([
    'order_id' => $rpOrderId,
    'config' => $config
]);
