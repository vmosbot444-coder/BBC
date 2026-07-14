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
require_once __DIR__ . '/../models/BotMessage.php';
require_once __DIR__ . '/../models/KeyReset.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::error('method_not_allowed', 405);

$input = json_decode(file_get_contents('php://input'), true);
$keyId = (int)($input['key_id'] ?? 0);
$telegramId = $input['telegram_id'] ?? '';

if (!$keyId || !$telegramId) Response::error('missing_params');

$pdo = Database::connect();
$stmt = $pdo->prepare("SELECT id, status FROM `keys` WHERE id = ?");
$stmt->execute([$keyId]);
$key = $stmt->fetch();

if (!$key) Response::error('Key not found');
if ($key['status'] !== 'active' && $key['status'] !== 'unused') {
    Response::json(['success' => false, 'reason' => 'invalid', 'message' => 'Only active keys can be reset']);
}

$result = KeyReset::performReset($keyId, $telegramId);

if (!$result['allowed']) {
    $limits = BotConfig::getResetLimits();

    if ($result['reason'] === 'daily_limit') {
        $msg = BotMessage::format('reset_limit', ['reset_limit' => $limits['daily_limit']]);
        Response::json(['success' => false, 'reason' => 'daily_limit', 'message' => $msg]);
    } elseif ($result['reason'] === 'cooldown') {
        $msg = BotMessage::format('reset_cooldown', ['wait_minutes' => $result['wait_minutes']]);
        Response::json(['success' => false, 'reason' => 'cooldown', 'message' => $msg]);
    }
}

$limits = BotConfig::getResetLimits();
$msg = BotMessage::format('reset_success', [
    'resets_left' => $limits['daily_limit'] - $result['resets_today'],
    'reset_limit' => $limits['daily_limit']
]);

try {
    Response::logActivity('bot_key_reset', ['key_id' => $keyId, 'telegram_id' => $telegramId]);
} catch (Exception $e) {}

Response::json(['success' => true, 'message' => $msg, 'resets_today' => $result['resets_today'], 'limit' => $limits['daily_limit']]);
