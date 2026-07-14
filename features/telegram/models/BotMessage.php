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
class BotMessage {
    private static $messages = [
        'welcome' => "*Welcome to SMART CHEAT*\n\nPremium game enhancement tools, instant activation.\nPurchase a key below to get started.",

        'key_delivery' => "*Payment Confirmed*\n\n*License Key*\n`{key}`\n\n*Plan:* {plan}\n*Valid Until:* {expiry}\n\nActivate in the SMART CHEAT app. Your key is ready to use immediately.",

        'reset_success' => "*Device Reset Complete*\n\nYour key has been unlinked from the previous device.\nResets used today: {resets_left}/{reset_limit}\n\nYou can now activate on a new device.",

        'reset_limit' => "*Daily Reset Limit Reached*\n\nYou have used all {reset_limit} resets for today.\nResets refresh at midnight. Contact support if urgent.",

        'reset_cooldown' => "*Cooldown Active*\n\nPlease wait {wait_minutes} minutes before your next reset.",

        'help' => "*SMART CHEAT — Help Center*\n\n*Buy Key* — Browse plans and purchase a license\n*My Keys* — View, manage, and reset your keys\n*Check Status* — Look up any key by entering it\n\nAll payments are secured via Razorpay.\nNeed assistance? Contact the admin."
    ];

    public static function get($type) {
        return self::$messages[$type] ?? '';
    }

    public static function format($type, $vars = []) {
        $msg = self::get($type);
        foreach ($vars as $k => $v) {
            $msg = str_replace('{' . $k . '}', $v, $msg);
        }
        return $msg;
    }
}
