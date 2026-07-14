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
require_once __DIR__ . '/../models/BotMessage.php';
require_once __DIR__ . '/../models/Plan.php';
require_once __DIR__ . '/TelegramAPI.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/handlers/StartHandler.php';
require_once __DIR__ . '/handlers/HelpHandler.php';

$config = BotConfig::get();
if (!$config || !$config['is_active'] || empty($config['bot_token'])) {
    http_response_code(200);
    exit;
}

$input = file_get_contents('php://input');
$update = json_decode($input, true);
if (!$update) {
    http_response_code(200);
    exit;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = "$protocol://$host";

try {
    $api = new TelegramAPI($config['bot_token']);
    $router = new BotRouter($api, $baseUrl);
    $router->handle($update);
} catch (Exception $e) {
    error_log("Bot webhook error: " . $e->getMessage());
}

http_response_code(200);
