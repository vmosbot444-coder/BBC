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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Check Status</title>
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
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#05060a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <div>
                    <div class="page-title">Check Status</div>
                    <div class="page-sub">Look up any license key</div>
                </div>
            </div>

            <div class="search-wrap">
                <input type="text" id="keyInput" placeholder="Enter license key...">
                <button class="search-btn" onclick="checkKey()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#05060a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
            </div>

            <div id="result"></div>
            <div id="status-msg"></div>
            <div class="footer">SMART CHEAT</div>
        </div>
    </div>

    <script>
        initTg();
        var tid = getParam('tid');
        var ci = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
        var mi = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>';
        var ri = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>';

        async function checkKey() {
            var key = document.getElementById('keyInput').value.trim();
            if (!key) return;
            var r = document.getElementById('result');
            r.innerHTML = '<div class="loading"><div class="spinner"></div><p style="font-size:12px;color:var(--text2);font-weight:600">Looking up...</p></div>';
            var res = await apiPost('/webapp/check_key.php', { key: key });
            if (!res.success) { r.innerHTML = ''; showAlert(document.getElementById('status-msg'), 'danger', res.error || 'Key not found'); return; }
            var k = res.key;
            var ex = k.status === 'expired';
            var sc = ex ? 'expired' : (k.device_linked ? 'active' : 'unused');
            var st = ex ? 'Expired' : (k.device_linked ? 'Active' : 'Unused');
            r.innerHTML = '<div class="card key-card ' + (ex ? 'expired' : '') + '">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px"><span class="card-title">Key Details</span><span class="badge badge-' + sc + '"><span class="badge-dot"></span> ' + st + '</span></div>' +
                '<div class="key-block">' + k.key_value + '</div>' +
                '<div class="key-info">' +
                    '<span class="key-info-item">' + ci + ' ' + k.expires_at + '</span>' +
                    '<span class="key-info-item">' + mi + ' ' + (k.device_linked ? 'Linked' : 'Not Linked') + '</span>' +
                    '<span class="key-info-item">' + ri + ' ' + k.resets_today + '/' + k.reset_limit + ' resets</span>' +
                '</div>' +
                (!ex ? '<div class="key-actions"><button class="btn btn-outline btn-sm" id="rb" onclick="resetKey(\'' + k.key_value + '\')">' + ri + ' Reset Device</button></div>' : '') +
            '</div>';
        }

        async function resetKey(kv) {
            if (!confirm('Reset device?')) return;
            var b = document.getElementById('rb');
            b.disabled = true; b.innerHTML = '<span class="spinner" style="width:12px;height:12px;margin:0;border-width:2px"></span>';
            var r = await apiPost('/webapp/reset_key.php', { key: kv, telegram_id: tid });
            if (r.success) { showAlert(document.getElementById('status-msg'), 'success', r.message || 'Reset done'); setTimeout(function(){checkKey()}, 1500); }
            else { showAlert(document.getElementById('status-msg'), 'danger', r.error || 'Failed'); b.disabled = false; b.innerHTML = ri + ' Reset Device'; }
        }
    </script>
</body>
</html>
