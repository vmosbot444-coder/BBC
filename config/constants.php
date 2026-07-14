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
define('APP_NAME', 'SMART CHEAT');
define('APP_VERSION', '2.0');
define('KEY_PREFIX', 'SMART CHEAT');
define('KEY_LENGTH', 20);
define('KEY_SEPARATOR', '-');
define('KEY_SEGMENT_LENGTH', 5);

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 300);

define('RATE_LIMIT_REQUESTS', 60);
define('RATE_LIMIT_WINDOW', 60);
define('RATE_LIMIT_BAN_THRESHOLD', 3);
define('RATE_LIMIT_BAN_DURATION', 3600);

define('SESSION_LIFETIME', 86400);
define('SESSION_NAME', 'SMART CHEAT_SESSION');

define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('ALLOWED_ARCHS', ['arm64-v8a', 'x86_64']);
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024);

define('DEFAULT_MAX_DEVICES', 1);
define('DEFAULT_DURATIONS', [
    ['days' => 7, 'label' => '7 Days', 'token_cost' => 1],
    ['days' => 15, 'label' => '15 Days', 'token_cost' => 2],
    ['days' => 30, 'label' => '30 Days', 'token_cost' => 3],
    ['days' => 90, 'label' => '90 Days', 'token_cost' => 8],
    ['days' => 365, 'label' => '365 Days', 'token_cost' => 25]
]);

define('BCRYPT_COST', 12);
