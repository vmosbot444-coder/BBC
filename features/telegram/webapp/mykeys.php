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

$tid = $_GET['tid'] ?? '';
$keys = [];
if ($tid) {
    $pdo = Database::connect();
    $stmt = $pdo->prepare("SELECT k.*, (SELECT COUNT(*) FROM devices WHERE key_id = k.id) as device_count FROM `keys` k INNER JOIN bot_orders o ON o.key_id = k.id INNER JOIN bot_users u ON u.id = o.bot_user_id WHERE u.telegram_id = ? ORDER BY k.created_at DESC");
    $stmt->execute([$tid]);
    $keys = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>My Keys</title>
    <link rel="stylesheet" href="assets/webapp.css?v=<?= time() ?>">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="assets/webapp.js?v=<?= time() ?>"></script>
</head>
<body>
    <div class="bg-mesh"></div>
    <div class="bg-grid"></div>
    <div class="page-wrap">
        <div class="content">
            <div class="page-header">
                <div class="page-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#05060a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                </div>
                <div>
                    <div class="page-title">My Keys</div>
                    <div class="page-sub"><?= count($keys) ?> license<?= count($keys) !== 1 ? 's' : '' ?> found</div>
                </div>
            </div>

            <div id="keys">
                <?php if (empty($keys)): ?>
                    <div class="empty">
                        <div class="empty-ring"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg></div>
                        <p>No keys purchased yet</p>
                        <a href="store.php?tid=<?= htmlspecialchars($tid) ?>" class="btn btn-accent btn-sm" style="width:auto;display:inline-flex;text-decoration:none">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#05060a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            Browse Store
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($keys as $k):
                        $isExpired = strtotime($k['expires_at']) < time();
                        $isUsed = $k['device_count'] > 0;
                        $status = $isExpired ? 'expired' : ($isUsed ? 'active' : 'unused');
                        $created = strtotime($k['created_at']);
                        $expires = strtotime($k['expires_at']);
                        $now = time();
                        $total = max($expires - $created, 1);
                        $remaining = max($expires - $now, 0);
                        $pct = $isExpired ? 0 : round(($remaining / $total) * 100);
                        $daysLeft = max(0, ceil($remaining / 86400));
                        $barClass = $pct > 50 ? '' : ($pct > 20 ? 'mid' : 'low');
                    ?>
                        <div class="card key-card <?= $isExpired ? 'expired' : ($isUsed ? '' : 'unused') ?>">
                            <div style="display:flex;justify-content:space-between;align-items:center">
                                <div class="card-title key-mono"><?= htmlspecialchars($k['key_value']) ?></div>
                                <span class="badge badge-<?= $status ?>"><span class="badge-dot"></span> <?= ucfirst($status) ?></span>
                            </div>

                            <?php if (!$isExpired): ?>
                                <div class="time-bar"><div class="time-bar-fill <?= $barClass ?>" style="width:<?= $pct ?>%"></div></div>
                                <div class="time-label"><?= $daysLeft ?> day<?= $daysLeft !== 1 ? 's' : '' ?> remaining</div>
                            <?php endif; ?>

                            <div class="key-info">
                                <span class="key-info-item">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <?= date('M j, Y', $expires) ?>
                                </span>
                                <span class="key-info-item">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                    <?= $isUsed ? 'Device Linked' : 'Not Linked' ?>
                                </span>
                            </div>

                            <?php if (!$isExpired): ?>
                                <div class="key-actions">
                                    <button class="btn btn-outline btn-sm" onclick="resetKey('<?= $k['key_value'] ?>', this)">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                        Reset
                                    </button>
                                    <a href="store.php?tid=<?= htmlspecialchars($tid) ?>" class="btn btn-accent btn-sm" style="text-decoration:none">Renew</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="status-msg"></div>
            <div class="footer">SMART CHEAT</div>
        </div>
    </div>

    <script>
        initTg();
        var ri = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>';
        async function resetKey(k, b) {
            if (!confirm('Reset device?')) return;
            b.disabled = true; b.innerHTML = '<span class="spinner" style="width:12px;height:12px;margin:0;border-width:2px"></span>';
            var r = await apiPost('/webapp/reset_key.php', { key: k, telegram_id: '<?= htmlspecialchars($tid) ?>' });
            if (r.success) { showAlert(document.getElementById('status-msg'), 'success', r.message || 'Device reset'); setTimeout(function(){location.reload()}, 1500); }
            else { showAlert(document.getElementById('status-msg'), 'danger', r.error || 'Failed'); b.disabled = false; b.innerHTML = ri + ' Reset'; }
        }
    </script>
</body>
</html>
