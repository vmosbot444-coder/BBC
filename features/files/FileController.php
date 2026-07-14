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
class FileController {
    public function list() {
        Auth::requireAdmin();
        $files = Database::connect()->query("SELECT f.*, a.username as uploaded_by_name FROM files f LEFT JOIN admins a ON a.id = f.uploaded_by ORDER BY f.uploaded_at DESC")->fetchAll();
        foreach ($files as &$f) {
            $f['file_size_formatted'] = Response::formatBytes($f['file_size']);
        }
        Response::success(['files' => $files]);
    }

    public function upload() {
        Auth::requireAdmin();
        $pdo = Database::connect();

        $arch = Response::sanitize(Response::require('arch'));
        $version = Response::sanitize(Response::require('version'));

        if (!in_array($arch, ALLOWED_ARCHS)) Response::error('invalid_arch');
        if (!isset($_FILES['file'])) Response::error('no_file_uploaded');

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) Response::error('upload_error_' . $file['error']);
        if ($file['size'] > MAX_UPLOAD_SIZE) Response::error('file_too_large');

        $uploadDir = UPLOAD_DIR . '/' . $arch . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = strtolower(APP_NAME) . '_' . $arch . '_' . str_replace('.', '_', $version) . '_' . time() . ($ext ? '.' . $ext : '');

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) Response::error('move_failed');

        $pdo->prepare("UPDATE files SET is_active = 0 WHERE arch = ?")->execute([$arch]);
        $pdo->prepare("INSERT INTO files (arch, filename, original_name, file_size, version, uploaded_by, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)")->execute([$arch, $filename, $file['name'], $file['size'], $version, Auth::userId()]);

        Response::logActivity('file_uploaded', ['arch' => $arch, 'version' => $version]);
        Response::success(['filename' => $filename]);
    }

    public function setActive() {
        Auth::requireAdmin();
        $pdo = Database::connect();
        $id = (int)Response::require('id');

        $file = $pdo->prepare("SELECT arch FROM files WHERE id = ?");
        $file->execute([$id]);
        $row = $file->fetch();
        if (!$row) Response::error('file_not_found');

        $pdo->prepare("UPDATE files SET is_active = 0 WHERE arch = ?")->execute([$row['arch']]);
        $pdo->prepare("UPDATE files SET is_active = 1 WHERE id = ?")->execute([$id]);
        Response::logActivity('file_activated', ['file_id' => $id]);
        Response::success();
    }

    public function delete() {
        Auth::requireAdmin();
        $pdo = Database::connect();
        $id = (int)Response::require('id');

        $file = $pdo->prepare("SELECT * FROM files WHERE id = ?");
        $file->execute([$id]);
        $row = $file->fetch();
        if (!$row) Response::error('file_not_found');

        $path = UPLOAD_DIR . '/' . $row['arch'] . '/' . $row['filename'];
        if (file_exists($path)) unlink($path);

        $pdo->prepare("DELETE FROM files WHERE id = ?")->execute([$id]);
        Response::logActivity('file_deleted', ['filename' => $row['filename']]);
        Response::success();
    }
}
