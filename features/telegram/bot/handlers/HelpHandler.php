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
class HelpHandler {
    private $api;
    private $baseUrl;

    public function __construct($api, $baseUrl) {
        $this->api = $api;
        $this->baseUrl = $baseUrl;
    }

    public function handle($chatId) {
        $config = BotConfig::get();

        $msg = "*SMART CHEAT — Help Center*\n\n"
             . "🛒 *Buy Key* — Browse available plans and purchase\n"
             . "🔑 *My Keys* — View your keys, check expiry, reset device\n"
             . "🔍 *Verify Key* — Look up any key's details\n\n"
             . "Payments are processed securely via Razorpay.\n"
             . "For support, contact the admin directly.";

        $webappBase = $this->baseUrl . '/features/telegram/webapp';

        $keyboard = [
            [
                ['text' => '🛒 Buy Key', 'web_app' => "$webappBase/store.php?tid={$chatId}"],
                ['text' => '🔑 My Keys', 'web_app' => "$webappBase/mykeys.php?tid={$chatId}"],
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
