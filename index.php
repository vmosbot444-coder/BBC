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
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/RateLimiter.php';
require_once __DIR__ . '/core/Response.php';

require_once __DIR__ . '/features/setup/SetupController.php';
require_once __DIR__ . '/features/setup/Schema.php';
require_once __DIR__ . '/features/auth/LoginController.php';
require_once __DIR__ . '/features/auth/ClientAuthController.php';
require_once __DIR__ . '/features/auth/FileDownloadController.php';
require_once __DIR__ . '/features/dashboard/DashboardController.php';
require_once __DIR__ . '/features/keys/KeyController.php';
require_once __DIR__ . '/features/keys/KeyGenerator.php';
require_once __DIR__ . '/features/keys/DeviceManager.php';
require_once __DIR__ . '/features/sellers/SellerController.php';
require_once __DIR__ . '/features/sellers/TokenManager.php';
require_once __DIR__ . '/features/files/FileController.php';
require_once __DIR__ . '/features/settings/SettingsController.php';
require_once __DIR__ . '/features/logs/LogController.php';

$router = new Router();

$router->get('/api/setup/status', [SetupController::class, 'status']);
$router->post('/api/setup/test-db', [SetupController::class, 'testDb']);
$router->post('/api/setup/install', [SetupController::class, 'install']);

$router->post('/api/login', [LoginController::class, 'login']);
$router->post('/api/logout', [LoginController::class, 'logout']);
$router->post('/api/account/update', [LoginController::class, 'updateAccount']);
$router->post('/api/client/auth', [ClientAuthController::class, 'handle']);
$router->get('/api/client/download', [FileDownloadController::class, 'handle']);

$router->get('/api/dashboard', [DashboardController::class, 'index']);

$router->get('/api/keys', [KeyController::class, 'list']);
$router->post('/api/keys/generate', [KeyController::class, 'generate']);
$router->post('/api/keys/edit', [KeyController::class, 'edit']);
$router->post('/api/keys/reset-device', [KeyController::class, 'resetDevice']);
$router->post('/api/keys/reset-all', [KeyController::class, 'resetAll']);
$router->post('/api/keys/delete-expired', [KeyController::class, 'deleteExpired']);
$router->post('/api/keys/ban', [KeyController::class, 'ban']);
$router->post('/api/keys/unban', [KeyController::class, 'unban']);
$router->post('/api/keys/delete', [KeyController::class, 'delete']);
$router->post('/api/keys/add-days', [KeyController::class, 'addDays']);

$router->get('/api/sellers', [SellerController::class, 'list']);
$router->post('/api/sellers/create', [SellerController::class, 'create']);
$router->post('/api/sellers/edit', [SellerController::class, 'edit']);
$router->post('/api/sellers/toggle', [SellerController::class, 'toggle']);
$router->post('/api/sellers/delete', [SellerController::class, 'delete']);
$router->get('/api/sellers/keys', [SellerController::class, 'keys']);
$router->post('/api/sellers/add-tokens', [TokenManager::class, 'addTokens']);
$router->post('/api/sellers/remove-tokens', [TokenManager::class, 'removeTokens']);
$router->post('/api/sellers/record-payment', [TokenManager::class, 'recordPayment']);
$router->get('/api/sellers/transactions', [TokenManager::class, 'transactions']);

$router->get('/api/files', [FileController::class, 'list']);
$router->post('/api/files/upload', [FileController::class, 'upload']);
$router->post('/api/files/set-active', [FileController::class, 'setActive']);
$router->post('/api/files/delete', [FileController::class, 'delete']);

$router->get('/api/settings', [SettingsController::class, 'get']);
$router->post('/api/settings/update', [SettingsController::class, 'update']);
$router->post('/api/settings/add-duration', [SettingsController::class, 'addDuration']);
$router->post('/api/settings/remove-duration', [SettingsController::class, 'removeDuration']);
$router->post('/api/settings/add-seller-contact', [SettingsController::class, 'addSellerContact']);
$router->post('/api/settings/remove-seller-contact', [SettingsController::class, 'removeSellerContact']);
$router->get('/api/client/sellers', [SettingsController::class, 'getSellerContacts']);

$router->get('/api/logs', [LogController::class, 'list']);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

if (str_starts_with($uri, '/api/')) {
    header('Content-Type: application/json');
    $router->dispatch($method, $uri);
    exit;
}

$installed = false;
try {
    $pdo = Database::connect();
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    $installed = $stmt->fetchColumn() > 0;
} catch (Exception $e) {}

switch (true) {
    case $uri === '/' || $uri === '':
        header('Location: ' . ($installed ? '/login' : '/setup'));
        exit;

    case $uri === '/setup':
        if ($installed) { header('Location: /login'); exit; }
        $router->serveFile(__DIR__ . '/features/setup/setup.html');
        break;

    case $uri === '/login':
        if (!$installed) { header('Location: /setup'); exit; }
        Auth::start();
        if (Auth::check()) { header('Location: /panel'); exit; }
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        $router->serveFile(__DIR__ . '/features/auth/login.html');
        break;

    case $uri === '/panel' || str_starts_with($uri, '/panel'):
        if (!$installed) { header('Location: /setup'); exit; }
        Auth::start();
        if (!Auth::check()) { header('Location: /login'); exit; }
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        $router->serveFile(__DIR__ . '/public/dashboard.html');
        break;

    default:
        http_response_code(404);
        echo '404';
}
