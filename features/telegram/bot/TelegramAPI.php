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
class TelegramAPI {
    private $token;
    private $apiBase;

    public function __construct($token = null) {
        $this->token = $token ?: BotConfig::getToken();
        $this->apiBase = "https://api.telegram.org/bot{$this->token}";
    }

    public function getMe() {
        return $this->request('getMe');
    }

    public function setWebhook($url) {
        return $this->request('setWebhook', ['url' => $url]);
    }

    public function deleteWebhook() {
        return $this->request('deleteWebhook');
    }

    public function sendMessage($chatId, $text, $replyMarkup = null, $parseMode = 'Markdown') {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode
        ];
        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }
        return $this->request('sendMessage', $data);
    }

    public function sendMessageWithInline($chatId, $text, $buttons) {
        $keyboard = [];
        foreach ($buttons as $row) {
            $kbRow = [];
            foreach ($row as $btn) {
                if (isset($btn['web_app'])) {
                    $kbRow[] = ['text' => $btn['text'], 'web_app' => ['url' => $btn['web_app']]];
                } elseif (isset($btn['url'])) {
                    $kbRow[] = ['text' => $btn['text'], 'url' => $btn['url']];
                } elseif (isset($btn['callback_data'])) {
                    $kbRow[] = ['text' => $btn['text'], 'callback_data' => $btn['callback_data']];
                } else {
                    $kbRow[] = ['text' => $btn['text'], 'callback_data' => 'noop'];
                }
            }
            $keyboard[] = $kbRow;
        }

        return $this->sendMessage($chatId, $text, [
            'inline_keyboard' => $keyboard
        ]);
    }

    public function answerCallbackQuery($callbackId, $text = '', $showAlert = false) {
        return $this->request('answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text' => $text,
            'show_alert' => $showAlert
        ]);
    }

    public function setMenuButton($chatId, $url, $text = 'Open Store') {
        return $this->request('setChatMenuButton', [
            'chat_id' => $chatId,
            'menu_button' => json_encode([
                'type' => 'web_app',
                'text' => $text,
                'web_app' => ['url' => $url]
            ])
        ]);
    }

    private function request($method, $data = []) {
        $ch = curl_init("{$this->apiBase}/{$method}");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result === false) return ['ok' => false, 'description' => 'cURL error'];
        $decoded = json_decode($result, true);
        return $decoded ?: ['ok' => false, 'description' => 'Invalid response'];
    }
}
