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
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/BotUser.php';
require_once __DIR__ . '/../models/KeyReset.php';
require_once __DIR__ . '/../models/BotConfig.php';

Auth::requireAdmin();

$stats = BotOrder::analytics();
$stats['total_users'] = BotUser::count();
$stats['resets_today'] = KeyReset::todayTotalResets();

Response::success(['analytics' => $stats]);
