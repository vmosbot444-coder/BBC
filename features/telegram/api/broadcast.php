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
require_once __DIR__ . '/../models/BotUser.php';
require_once __DIR__ . '/../bot/TelegramAPI.php';

Auth::requireAdmin();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'history';

function getTargetUserIds($target) {
    $pdo = Database::connect();
    switch ($target) {
        case 'active':
            return $pdo->query("SELECT DISTINCT bu.telegram_id FROM bot_users bu INNER JOIN bot_orders o ON o.bot_user_id = bu.id INNER JOIN `keys` k ON k.id = o.key_id WHERE k.expires_at > NOW()")->fetchAll(PDO::FETCH_COLUMN);
        case 'expired':
            return $pdo->query("SELECT DISTINCT bu.telegram_id FROM bot_users bu INNER JOIN bot_orders o ON o.bot_user_id = bu.id INNER JOIN `keys` k ON k.id = o.key_id WHERE k.expires_at <= NOW() AND bu.id NOT IN (SELECT o2.bot_user_id FROM bot_orders o2 INNER JOIN `keys` k2 ON k2.id = o2.key_id WHERE k2.expires_at > NOW())")->fetchAll(PDO::FETCH_COLUMN);
        case 'no_purchase':
            return $pdo->query("SELECT bu.telegram_id FROM bot_users bu WHERE bu.id NOT IN (SELECT DISTINCT bot_user_id FROM bot_orders WHERE status = 'paid')")->fetchAll(PDO::FETCH_COLUMN);
        default:
            return $pdo->query("SELECT telegram_id FROM bot_users")->fetchAll(PDO::FETCH_COLUMN);
    }
}

switch ($action) {
    case 'send':
        if ($method !== 'POST') Response::error('method_not_allowed', 405);
        $input = Response::input();
        $message = $input['message'] ?? '';
        $target = $input['target'] ?? 'all';
        $buttons = $input['buttons'] ?? [];
        if (!$message) Response::error('missing_message');

        $config = BotConfig::get();
        if (!$config['bot_token']) Response::error('bot_not_connected');

        $api = new TelegramAPI($config['bot_token']);
        $userIds = getTargetUserIds($target);
        $sent = 0;
        $failed = 0;

        // Build inline keyboard from buttons
        $replyMarkup = null;
        if (!empty($buttons)) {
            $keyboard = [];
            foreach ($buttons as $btn) {
                $text = trim($btn['text'] ?? '');
                $url = trim($btn['url'] ?? '');
                if ($text && $url) {
                    $keyboard[] = [['text' => $text, 'url' => $url]];
                }
            }
            if (!empty($keyboard)) {
                $replyMarkup = ['inline_keyboard' => $keyboard];
            }
        }

        foreach ($userIds as $tid) {
            $result = $api->sendMessage($tid, $message, $replyMarkup);
            if ($result['ok'] ?? false) $sent++;
            else $failed++;
            usleep(35000);
        }

        $pdo = Database::connect();
        $pdo->prepare("INSERT INTO bot_broadcasts (message, recipient_count, target_type) VALUES (?, ?, ?)")
            ->execute([$message, $sent, $target]);

        Response::logActivity('bot_broadcast', ['sent' => $sent, 'failed' => $failed, 'target' => $target]);
        Response::success(['sent' => $sent, 'failed' => $failed, 'total' => count($userIds)]);
        break;

    case 'count':
        $target = $_GET['target'] ?? 'all';
        $userIds = getTargetUserIds($target);
        Response::success(['count' => count($userIds)]);
        break;

    case 'history':
        $pdo = Database::connect();
        $broadcasts = $pdo->query("SELECT * FROM bot_broadcasts ORDER BY sent_at DESC LIMIT 50")->fetchAll();
        Response::success(['broadcasts' => $broadcasts]);
        break;

    default:
        Response::error('unknown_action');
}
