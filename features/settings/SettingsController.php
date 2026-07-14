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
class SettingsController {
    public function get() {
        Auth::requireAuth();
        $pdo = Database::connect();

        if (Auth::isSeller()) {
            $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'duration_options'");
            Response::success(['duration_options' => json_decode($stmt->fetchColumn(), true)]);
        }

        $settings = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
        $result = [];
        foreach ($settings as $s) $result[$s['setting_key']] = $s['setting_value'];
        Response::success(['settings' => $result]);
    }

    public function update() {
        Auth::requireAdmin();
        $key = Response::sanitize(Response::require('setting_key'));
        $value = Response::param('setting_value', '');

        Database::connect()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$key, $value, $value]);
        Response::logActivity('setting_updated', ['key' => $key]);
        Response::success();
    }

    public function addDuration() {
        Auth::requireAdmin();
        $pdo = Database::connect();

        $days = (int)Response::require('days');
        $label = Response::sanitize(Response::require('label'));
        $tokenCost = (int)Response::require('token_cost');

        $options = json_decode($pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'duration_options'")->fetchColumn(), true) ?: [];

        foreach ($options as $opt) {
            if ($opt['days'] == $days) Response::error('duration_exists');
        }

        $options[] = ['days' => $days, 'label' => $label, 'token_cost' => $tokenCost];
        usort($options, fn($a, $b) => $a['days'] - $b['days']);

        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'duration_options'")->execute([json_encode($options)]);
        Response::logActivity('duration_added', ['days' => $days]);
        Response::success(['options' => $options]);
    }

    public function removeDuration() {
        Auth::requireAdmin();
        $pdo = Database::connect();

        $days = (int)Response::require('days');
        $options = json_decode($pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'duration_options'")->fetchColumn(), true) ?: [];
        $options = array_values(array_filter($options, fn($o) => $o['days'] != $days));

        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'duration_options'")->execute([json_encode($options)]);
        Response::logActivity('duration_removed', ['days' => $days]);
        Response::success(['options' => $options]);
    }

    public function addSellerContact() {
        Auth::requireAdmin();
        $pdo = Database::connect();

        $name = Response::sanitize(Response::require('name'));
        $telegram = Response::sanitize(Response::param('telegram', ''));
        $whatsapp = Response::sanitize(Response::param('whatsapp', ''));

        if (!$telegram && !$whatsapp) Response::error('at_least_one_contact');

        $contacts = json_decode($pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'seller_contacts'")->fetchColumn(), true) ?: [];

        $contacts[] = ['name' => $name, 'telegram' => $telegram, 'whatsapp' => $whatsapp];

        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('seller_contacts', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([json_encode($contacts), json_encode($contacts)]);
        Response::logActivity('seller_contact_added', ['name' => $name]);
        Response::success(['contacts' => $contacts]);
    }

    public function removeSellerContact() {
        Auth::requireAdmin();
        $pdo = Database::connect();

        $index = (int)Response::require('index');
        $contacts = json_decode($pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'seller_contacts'")->fetchColumn(), true) ?: [];

        if (!isset($contacts[$index])) Response::error('invalid_index');

        $removed = $contacts[$index]['name'];
        array_splice($contacts, $index, 1);

        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'seller_contacts'")->execute([json_encode($contacts)]);
        Response::logActivity('seller_contact_removed', ['name' => $removed]);
        Response::success(['contacts' => $contacts]);
    }

    public function getSellerContacts() {
        $pdo = Database::connect();
        $contacts = json_decode($pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'seller_contacts'")->fetchColumn(), true) ?: [];
        Response::success(['contacts' => $contacts]);
    }
}
