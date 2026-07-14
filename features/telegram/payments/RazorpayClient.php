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
class RazorpayClient {
    private $keyId;
    private $keySecret;
    private $baseUrl = 'https://api.razorpay.com/v1';

    public function __construct() {
        $rp = BotConfig::getRazorpay();
        $this->keyId = $rp['key_id'];
        $this->keySecret = $rp['key_secret'];
    }

    public function createOrder($amountPaise, $currency = 'INR', $receipt = '', $notes = []) {
        $data = [
            'amount' => $amountPaise,
            'currency' => $currency,
            'receipt' => $receipt ?: 'order_' . time(),
            'notes' => $notes ?: new \stdClass()
        ];
        return $this->request('POST', '/orders', $data);
    }

    public function fetchPayment($paymentId) {
        return $this->request('GET', "/payments/$paymentId");
    }

    public function fetchOrder($orderId) {
        return $this->request('GET', "/orders/$orderId");
    }

    public function verifyWebhookSignature($payload, $signature, $secret = null) {
        if (!$secret) {
            $rp = BotConfig::getRazorpay();
            $secret = $rp['webhook_secret'];
        }
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    public function verifyPaymentSignature($orderId, $paymentId, $signature) {
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);
        return hash_equals($expected, $signature);
    }

    public function getCheckoutConfig($orderId, $amount, $planName, $prefill = []) {
        return [
            'key' => $this->keyId,
            'amount' => $amount,
            'currency' => 'INR',
            'name' => APP_NAME,
            'description' => $planName,
            'order_id' => $orderId,
            'prefill' => $prefill,
            'theme' => ['color' => '#00FF87']
        ];
    }

    private function request($method, $path, $data = null) {
        $ch = curl_init($this->baseUrl . $path);
        $headers = ['Content-Type: application/json'];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERPWD => "{$this->keyId}:{$this->keySecret}",
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result === false) return ['error' => 'cURL failed'];
        $decoded = json_decode($result, true);
        if ($httpCode >= 400) return ['error' => $decoded['error']['description'] ?? 'API error', 'code' => $httpCode];
        return $decoded;
    }
}
