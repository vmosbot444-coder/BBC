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
require_once __DIR__ . '/../models/KeyReset.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::error('method_not_allowed', 405);

$input = json_decode(file_get_contents('php://input'), true);
$keyStr = strtoupper(trim($input['key'] ?? ''));
$telegramId = $input['telegram_id'] ?? '';

if (!$keyStr) Response::error('missing_key');

$pdo = Database::connect();
$stmt = $pdo->prepare("SELECT id, license_key, status, duration_days, device_count, activated_at, expires_at FROM `keys` WHERE license_key = ?");
$stmt->execute([$keyStr]);
$key = $stmt->fetch();

if (!$key) Response::error('Key not found');

if ($key['status'] === 'active' && $key['expires_at'] && strtotime($key['expires_at']) < time()) {
    $pdo->prepare("UPDATE `keys` SET status = 'expired' WHERE id = ?")->execute([$key['id']]);
    $key['status'] = 'expired';
}

$limits = BotConfig::getResetLimits();
$resetsToday = KeyReset::getResetsToday($key['id']);

$cooldownRemaining = 0;
$stmt2 = $pdo->prepare("SELECT reset_at FROM bot_key_resets WHERE key_id = ? ORDER BY reset_at DESC LIMIT 1");
$stmt2->execute([$key['id']]);
$lastReset = $stmt2->fetch();
if ($lastReset) {
    $cooldownEnd = strtotime($lastReset['reset_at']) + ($limits['cooldown_minutes'] * 60);
    if (time() < $cooldownEnd) {
        $cooldownRemaining = ceil(($cooldownEnd - time()) / 60);
    }
}

Response::success(['key' => [
    'id' => (int)$key['id'],
    'key_value' => $key['license_key'],
    'status' => $key['status'],
    'duration_days' => (int)$key['duration_days'],
    'device_linked' => (int)$key['device_count'] > 0,
    'expires_at' => $key['expires_at'],
    'resets_today' => $resetsToday,
    'reset_limit' => $limits['daily_limit'],
    'cooldown_remaining' => $cooldownRemaining
]]);
