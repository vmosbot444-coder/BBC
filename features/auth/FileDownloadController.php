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
class FileDownloadController {
    public function handle() {
        $token = Response::sanitize($_GET['token'] ?? '');
        if (!$token) Response::error('missing_token', 403);

        $pdo = Database::connect();

        $stmt = $pdo->prepare("SELECT * FROM client_sessions WHERE download_token = ? AND status = 'created' AND expires_at > NOW()");
        $stmt->execute([$token]);
        $session = $stmt->fetch();

        if (!$session) Response::error('invalid_or_expired_token', 403);

        $fileStmt = $pdo->prepare("SELECT filename FROM files WHERE arch = ? AND is_active = 1 ORDER BY uploaded_at DESC LIMIT 1");
        $fileStmt->execute([$session['arch']]);
        $file = $fileStmt->fetch();

        if (!$file) Response::error('no_file_available', 404);

        $filePath = __DIR__ . '/../../uploads/' . $session['arch'] . '/' . $file['filename'];
        if (!file_exists($filePath)) Response::error('file_not_found', 404);

        $pdo->prepare("UPDATE client_sessions SET status = 'downloaded' WHERE id = ?")->execute([$session['id']]);

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: attachment; filename="SMART CHEAT"');
        header('Cache-Control: no-store');
        readfile($filePath);
        exit;
    }
}
