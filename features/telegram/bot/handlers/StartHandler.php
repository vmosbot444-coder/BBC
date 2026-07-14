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
class StartHandler {
    private $api;
    private $baseUrl;

    public function __construct($api, $baseUrl) {
        $this->api = $api;
        $this->baseUrl = $baseUrl;
    }

    public function handle($chatId, $from = []) {
        $firstName = $from['first_name'] ?? 'there';
        $user = BotUser::getByTelegramId($chatId);
        $config = BotConfig::get();

        $pdo = Database::connect();
        $activeKeys = 0;
        $totalOrders = 0;

        if ($user) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `keys` k INNER JOIN bot_orders o ON o.key_id = k.id WHERE o.bot_user_id = ? AND k.expires_at > NOW()");
            $stmt->execute([$user['id']]);
            $activeKeys = (int)$stmt->fetchColumn();

            $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM bot_orders WHERE bot_user_id = ? AND status = 'paid'");
            $stmt2->execute([$user['id']]);
            $totalOrders = (int)$stmt2->fetchColumn();
        }

        if (!$user || $totalOrders === 0) {
            $msg = "*Hey {$firstName}, welcome to SMART CHEAT!*\n\n"
                 . "Premium game enhancement tools.\n"
                 . "Fast activation, secure payments.\n\n"
                 . "Get started by purchasing your first key below.";
        } elseif ($activeKeys > 0) {
            $msg = "*Welcome back, {$firstName}!*\n\n"
                 . "Active Keys: *{$activeKeys}*  |  Purchases: *{$totalOrders}*\n\n"
                 . "Manage your keys or grab another one.";
        } else {
            $msg = "*Welcome back, {$firstName}!*\n\n"
                 . "You have no active keys right now.\n"
                 . "Renew below to get back in the game.";
        }

        $webappBase = $this->baseUrl . '/features/telegram/webapp';

        $keyboard = [
            [
                ['text' => '🛒 Buy Key', 'web_app' => "$webappBase/store.php?tid={$chatId}"],
                ['text' => '🔑 My Keys', 'web_app' => "$webappBase/mykeys.php?tid={$chatId}"],
            ],
            [
                ['text' => '🔍 Verify Key', 'web_app' => "$webappBase/status.php?tid={$chatId}"],
                ['text' => '❓ Help', 'callback_data' => 'help'],
            ]
        ];

        $extraRow = [];
        if (!empty($config['apk_download_url'])) {
            $extraRow[] = ['text' => '📱 Download App', 'url' => $config['apk_download_url']];
        }
        if (!empty($config['setup_video_url'])) {
            $extraRow[] = ['text' => '📺 Setup Guide', 'url' => $config['setup_video_url']];
        }
        if (!empty($extraRow)) {
            $keyboard[] = $extraRow;
        }

        $this->api->sendMessageWithInline($chatId, $msg, $keyboard);
    }
}
