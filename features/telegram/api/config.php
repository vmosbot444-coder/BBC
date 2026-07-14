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
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../models/BotConfig.php';
require_once __DIR__ . '/../bot/TelegramAPI.php';

Auth::requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'get';

switch ($action) {
    case 'get':
        $config = BotConfig::get();
        $config['bot_token_masked'] = $config['bot_token'] ? substr($config['bot_token'], 0, 10) . '...' : '';
        $config['rp_key_secret_masked'] = $config['rp_key_secret'] ? '••••••••' : '';
        $config['rp_webhook_secret_masked'] = $config['rp_webhook_secret'] ? '••••••••' : '';
        unset($config['rp_key_secret'], $config['rp_webhook_secret']);
        Response::success(['config' => $config]);
        break;

    case 'save':
        if ($method !== 'POST') Response::error('method_not_allowed', 405);
        $input = Response::input();
        if (isset($input['rp_key_id'])) {
            if (strpos($input['rp_key_id'], 'rzp_live') === 0) {
                $input['rp_mode'] = 'live';
            } elseif (strpos($input['rp_key_id'], 'rzp_test') === 0) {
                $input['rp_mode'] = 'test';
            }
        }
        BotConfig::update($input);
        Response::logActivity('bot_config_update', $input);
        Response::success();
        break;

    case 'connect':
        if ($method !== 'POST') Response::error('method_not_allowed', 405);
        $input = Response::input();
        $token = $input['bot_token'] ?? '';
        if (!$token) Response::error('missing_token');

        $api = new TelegramAPI($token);
        $me = $api->getMe();
        if (!($me['ok'] ?? false)) Response::error('invalid_token');

        $botInfo = $me['result'];

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $webhookUrl = "$protocol://$host/features/telegram/bot/webhook.php";

        $whResult = $api->setWebhook($webhookUrl);
        if (!($whResult['ok'] ?? false)) Response::error('webhook_failed: ' . ($whResult['description'] ?? ''));

        BotConfig::update([
            'bot_token' => $token,
            'bot_username' => $botInfo['username'] ?? '',
            'bot_name' => $botInfo['first_name'] ?? '',
            'is_active' => 1
        ]);

        Response::logActivity('bot_connected', ['username' => $botInfo['username']]);
        Response::success([
            'bot' => $botInfo,
            'webhook_url' => $webhookUrl
        ]);
        break;

    case 'disconnect':
        if ($method !== 'POST') Response::error('method_not_allowed', 405);
        $config = BotConfig::get();
        if ($config['bot_token']) {
            $api = new TelegramAPI($config['bot_token']);
            $api->deleteWebhook();
        }
        BotConfig::update(['is_active' => 0, 'bot_token' => '', 'bot_username' => '', 'bot_name' => '']);
        Response::logActivity('bot_disconnected');
        Response::success();
        break;

    default:
        Response::error('unknown_action');
}
