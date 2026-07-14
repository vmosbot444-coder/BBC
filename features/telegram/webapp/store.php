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
require_once __DIR__ . '/../models/Plan.php';

$plans = Plan::listActive();
$tid = $_GET['tid'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>SMART CHEAT Store</title>
    <link rel="stylesheet" href="assets/webapp.css?v=<?= time() ?>">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="assets/webapp.js?v=<?= time() ?>"></script>
</head>
<body>
    <div class="bg-mesh"></div>
    <div class="bg-grid"></div>
    <div class="page-wrap">
        <div class="content">
            <div class="page-header">
                <div class="page-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#05060a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
                <div>
                    <div class="page-title">Store</div>
                    <div class="page-sub">Choose your plan</div>
                </div>
            </div>

            <div id="plans">
                <?php if (empty($plans)): ?>
                    <div class="empty">
                        <div class="empty-ring"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg></div>
                        <p>No plans available right now</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($plans as $p):
                        $effective = $p['discount_price_paise'] ?: $p['price_paise'];
                        $discount = 0;
                        if ($p['discount_price_paise'] && $p['discount_price_paise'] < $p['price_paise']) {
                            $discount = round((1 - $p['discount_price_paise'] / $p['price_paise']) * 100);
                        }
                    ?>
                        <div class="card card-glow" id="plan-<?= $p['id'] ?>">
                            <?php if ($discount > 0): ?>
                                <div class="discount-pill"><?= $discount ?>% OFF</div>
                            <?php endif; ?>

                            <div class="card-title"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="plan-meta">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <?= $p['duration_days'] ?> day<?= $p['duration_days'] > 1 ? 's' : '' ?> access
                            </div>

                            <div class="price-row">
                                <span class="price-symbol">&#8377;</span>
                                <span class="price-big"><?= number_format($effective / 100) ?></span>
                                <?php if ($discount > 0): ?>
                                    <span class="price-old">&#8377;<?= number_format($p['price_paise'] / 100) ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($p['description']): ?>
                                <div class="card-sub" style="margin-bottom:18px"><?= htmlspecialchars($p['description']) ?></div>
                            <?php endif; ?>

                            <button class="btn btn-accent" onclick="buyPlan(<?= $p['id'] ?>)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#05060a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Purchase Now
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="status-msg"></div>

            <div class="secure-bar">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Secured by Razorpay &middot; 256-bit SSL
            </div>
            <div class="footer">SMART CHEAT</div>
        </div>
    </div>

    <script>
        initTg();
        var tid = '<?= htmlspecialchars($tid) ?>';
        var ico = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#05060a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';

        async function buyPlan(id) {
            var b = document.querySelector('#plan-' + id + ' .btn');
            b.disabled = true;
            b.innerHTML = '<span class="spinner" style="width:16px;height:16px;margin:0;border-width:2px"></span> Processing...';
            try {
                var r = await apiPost('/payments/create_order.php', { plan_id: id, telegram_id: tid });
                if (!r.success) { showAlert(document.getElementById('status-msg'), 'danger', r.error || 'Failed'); b.disabled = false; b.innerHTML = ico + ' Purchase Now'; return; }
                var c = r.config;
                c.handler = async function(res) {
                    b.innerHTML = '<span class="spinner" style="width:16px;height:16px;margin:0;border-width:2px"></span> Verifying...';
                    var v = await apiPost('/payments/verify_payment.php', { razorpay_order_id: res.razorpay_order_id, razorpay_payment_id: res.razorpay_payment_id, razorpay_signature: res.razorpay_signature });
                    if (v.success) { showAlert(document.getElementById('status-msg'), 'success', 'Payment successful! Key delivered.'); setTimeout(function() { closeTg(); }, 2000); }
                    else { showAlert(document.getElementById('status-msg'), 'danger', v.error || 'Verification failed'); b.disabled = false; b.innerHTML = ico + ' Purchase Now'; }
                };
                c.modal = { ondismiss: function() { b.disabled = false; b.innerHTML = ico + ' Purchase Now'; } };
                new Razorpay(c).open();
            } catch(e) { showAlert(document.getElementById('status-msg'), 'danger', 'Network error'); b.disabled = false; b.innerHTML = ico + ' Purchase Now'; }
        }
    </script>
</body>
</html>
