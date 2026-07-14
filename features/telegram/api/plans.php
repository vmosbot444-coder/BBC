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
require_once __DIR__ . '/../models/Plan.php';

Auth::requireAdmin();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        Response::success(['plans' => Plan::listAll()]);
        break;

    case 'create':
        if ($method !== 'POST') Response::error('method_not_allowed', 405);
        $input = Response::input();
        if (empty($input['name']) || empty($input['duration_days']) || empty($input['price_paise']))
            Response::error('missing_fields');
        $id = Plan::create($input);
        Response::logActivity('bot_plan_created', $input);
        Response::success(['id' => $id]);
        break;

    case 'update':
        if ($method !== 'POST') Response::error('method_not_allowed', 405);
        $input = Response::input();
        $id = $input['id'] ?? null;
        if (!$id) Response::error('missing_id');
        Plan::update($id, $input);
        Response::logActivity('bot_plan_updated', $input);
        Response::success();
        break;

    case 'delete':
        if ($method !== 'POST') Response::error('method_not_allowed', 405);
        $input = Response::input();
        $id = $input['id'] ?? null;
        if (!$id) Response::error('missing_id');
        Plan::delete($id);
        Response::logActivity('bot_plan_deleted', ['id' => $id]);
        Response::success();
        break;

    case 'toggle':
        if ($method !== 'POST') Response::error('method_not_allowed', 405);
        $input = Response::input();
        $id = $input['id'] ?? null;
        if (!$id) Response::error('missing_id');
        $plan = Plan::getById($id);
        if (!$plan) Response::error('not_found');
        Plan::update($id, ['is_active' => $plan['is_active'] ? 0 : 1]);
        Response::success();
        break;

    default:
        Response::error('unknown_action');
}
