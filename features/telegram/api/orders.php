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

Auth::requireAdmin();

$page = (int)($_GET['page'] ?? 1);
$status = $_GET['status'] ?? null;
$search = $_GET['search'] ?? '';

$result = BotOrder::listAll($page, 50, $status, $search);
Response::success($result);
