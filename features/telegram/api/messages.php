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
require_once __DIR__ . '/../models/BotMessage.php';

Auth::requireAdmin();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    Response::success(['messages' => BotMessage::getAll()]);
} elseif ($method === 'POST') {
    $input = Response::input();
    $messages = $input['messages'] ?? [];
    foreach ($messages as $m) {
        if (!empty($m['msg_type']) && isset($m['content'])) {
            BotMessage::set($m['msg_type'], $m['content']);
        }
    }
    Response::logActivity('bot_messages_updated');
    Response::success();
} else {
    Response::error('method_not_allowed', 405);
}
