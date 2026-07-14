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
class BotRouter {
    private $api;
    private $baseUrl;

    public function __construct($api, $baseUrl) {
        $this->api = $api;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function handle($update) {
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
        }
    }

    private function handleMessage($msg) {
        $chatId = $msg['chat']['id'];
        $text = trim($msg['text'] ?? '');
        $from = $msg['from'] ?? [];

        BotUser::findOrCreate($from['id'] ?? $chatId, [
            'username' => $from['username'] ?? '',
            'first_name' => $from['first_name'] ?? '',
            'last_name' => $from['last_name'] ?? ''
        ]);

        if ($text === '/help') {
            $handler = new HelpHandler($this->api, $this->baseUrl);
            $handler->handle($chatId);
        } else {
            $handler = new StartHandler($this->api, $this->baseUrl);
            $handler->handle($chatId, $from);
        }
    }

    private function handleCallback($callback) {
        $chatId = $callback['message']['chat']['id'] ?? null;
        $data = $callback['data'] ?? '';

        $this->api->answerCallbackQuery($callback['id']);

        if ($data === 'help' && $chatId) {
            $handler = new HelpHandler($this->api, $this->baseUrl);
            $handler->handle($chatId);
        }
    }
}
