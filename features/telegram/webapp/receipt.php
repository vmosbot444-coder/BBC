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

$orderId = $_GET['order'] ?? '';
$order = null;
if ($orderId) {
    $pdo = Database::connect();
    $stmt = $pdo->prepare("SELECT o.*, p.name as plan_name, p.duration_days, k.key_value, k.expires_at FROM bot_orders o JOIN bot_plans p ON p.id = o.plan_id LEFT JOIN `keys` k ON k.id = o.key_id WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Receipt</title>
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
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#05060a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <div>
                    <div class="page-title">Receipt</div>
                    <div class="page-sub">Payment confirmation</div>
                </div>
            </div>

            <?php if (!$order): ?>
                <div class="empty">
                    <div class="empty-ring"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                    <p>Receipt not found</p>
                </div>
            <?php else: ?>
                <div class="receipt">
                    <div class="receipt-top">
                        <div class="receipt-check">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#00FF87" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <div class="receipt-amount">&#8377;<?= number_format($order['amount_paise'] / 100) ?></div>
                        <div class="receipt-label">Payment Successful</div>
                    </div>
                    <div class="receipt-body">
                        <div class="receipt-line"><span>Plan</span><span><?= htmlspecialchars($order['plan_name']) ?></span></div>
                        <div class="receipt-line"><span>Duration</span><span><?= $order['duration_days'] ?> days</span></div>
                        <div class="receipt-line"><span>Order</span><span style="font-family:monospace;font-size:11px;letter-spacing:1px">#<?= $order['id'] ?></span></div>
                        <div class="receipt-line"><span>Date</span><span><?= date('M j, Y', strtotime($order['paid_at'] ?: $order['created_at'])) ?></span></div>
                        <?php if ($order['razorpay_payment_id']): ?>
                            <div class="receipt-line"><span>Txn ID</span><span style="font-family:monospace;font-size:11px;letter-spacing:1px"><?= $order['razorpay_payment_id'] ?></span></div>
                        <?php endif; ?>
                        <div class="receipt-line receipt-total"><span>Total</span><span>&#8377;<?= number_format($order['amount_paise'] / 100) ?></span></div>
                    </div>
                    <?php if ($order['key_value']): ?>
                        <div class="receipt-key-section">
                            <div class="receipt-key-tag">License Key</div>
                            <div class="key-block"><?= htmlspecialchars($order['key_value']) ?></div>
                            <div style="font-size:11px;color:var(--text2);font-weight:600">Valid until <?= date('M j, Y', strtotime($order['expires_at'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="footer">SMART CHEAT</div>
        </div>
    </div>
    <script>initTg();</script>
</body>
</html>
