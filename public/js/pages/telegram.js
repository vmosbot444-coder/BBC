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
async function renderTelegram(c) {
    c.innerHTML = '<div class="loading-text">Loading bot settings...</div>';

    const [configRes, plansRes, analyticsRes, ordersRes, usersRes, broadcastRes] = await Promise.all([
        tgApi('config.php?action=get'),
        tgApi('plans.php?action=list'),
        tgApi('analytics.php'),
        tgApi('orders.php'),
        tgApi('users.php'),
        tgApi('broadcast.php?action=history')
    ]);

    const cfg = configRes?.config || {};
    const plans = plansRes?.plans || [];
    const stats = analyticsRes?.analytics || {};
    const orders = ordersRes?.orders || [];
    const totalOrders = ordersRes?.total || 0;
    const users = usersRes?.users || [];
    const totalUsers = usersRes?.total || 0;
    const broadcasts = broadcastRes?.broadcasts || [];

    c.innerHTML = `
        <div class="stats-row">
            <div class="stat-card"><div class="stat-label">Today</div><div class="stat-value" id="rev-today">0</div><div class="stat-sub" id="cnt-today">0 orders</div></div>
            <div class="stat-card"><div class="stat-label">7 Days</div><div class="stat-value" id="rev-week">0</div><div class="stat-sub" id="cnt-week">0 orders</div></div>
            <div class="stat-card"><div class="stat-label">30 Days</div><div class="stat-value" id="rev-month">0</div><div class="stat-sub" id="cnt-month">0 orders</div></div>
            <div class="stat-card accent"><div class="stat-label">All Time</div><div class="stat-value" id="rev-all">0</div><div class="stat-sub" id="cnt-all">0 orders</div></div>
        </div>

        <div class="row-2col">
            <div class="card">
                <div class="card-header"><span>Bot Setup</span>
                    <span class="badge ${cfg.is_active ? 'badge-active' : 'badge-expired'}" id="botStatus">${cfg.is_active ? 'Active' : 'Off'}</span>
                </div>
                <div class="card-body">
                    ${cfg.is_active ? `
                        <div class="info-row"><span class="info-label">Bot</span><span>@${cfg.bot_username || '\u2014'}</span></div>
                        <div class="info-row"><span class="info-label">Name</span><span>${cfg.bot_name || '\u2014'}</span></div>
                        <div class="info-row"><span class="info-label">Webhook</span><span class="text-dim text-xs">${window.location.origin}/features/telegram/bot/webhook.php</span></div>
                        <button class="btn btn-danger btn-sm" style="margin-top:12px" onclick="disconnectBot()">Disconnect Bot</button>
                    ` : `
                        <div class="form-group"><label class="form-label">Bot Token</label><input class="form-input" id="botToken" type="password" placeholder="Paste from @BotFather"></div>
                        <button class="btn btn-primary btn-sm" onclick="connectBot()">Connect Bot</button>
                    `}
                </div>
            </div>

            <div class="card">
                <div class="card-header"><span>Razorpay</span>
                    <span class="badge ${cfg.rp_key_id ? 'badge-active' : 'badge-expired'}">${cfg.rp_key_id ? 'Configured' : 'Not Set'}</span>
                </div>
                <div class="card-body">
                    <div class="form-group"><label class="form-label">Key ID</label><input class="form-input" id="rpKeyId" value="${cfg.rp_key_id || ''}" placeholder="rzp_test_..."></div>
                    <div class="form-group"><label class="form-label">Key Secret</label><input class="form-input" id="rpKeySecret" type="password" placeholder="${cfg.rp_key_secret_masked || 'Enter secret'}"></div>
                    <button class="btn btn-primary btn-sm" onclick="saveRazorpay()">Save Razorpay</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span>Bot Settings</span></div>
            <div class="card-body">
                <div class="row-2col">
                    <div class="form-group"><label class="form-label">Reset Limit Per Day</label><input class="form-input" id="resetLimit" type="number" value="${cfg.reset_limit_per_day || 3}" min="1" max="50"></div>
                    <div class="form-group"><label class="form-label">Reset Cooldown (minutes)</label><input class="form-input" id="resetCooldown" type="number" value="${cfg.reset_cooldown_minutes || 30}" min="1" max="1440"></div>
                </div>
                <div class="form-group"><label class="form-label">APK Download URL</label><input class="form-input" id="apkUrl" value="${cfg.apk_download_url || ''}" placeholder="https://drive.google.com/... or direct link"></div>
                <div class="form-group"><label class="form-label">Setup Video URL</label><input class="form-input" id="setupUrl" value="${cfg.setup_video_url || ''}" placeholder="https://youtube.com/watch?v=..."></div>
                <button class="btn btn-primary btn-sm" onclick="saveBotSettings()">Save Settings</button>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span>Plans</span><button class="btn btn-primary btn-sm" onclick="showPlanModal()">+ Add Plan</button></div>
            <div class="card-body" id="plansTable"></div>
        </div>

        <div class="card">
            <div class="card-header"><span>Orders</span>
                <div class="filter-pills">
                    <span class="pill active" onclick="filterOrders(null, this)">All</span>
                    <span class="pill" onclick="filterOrders('paid', this)">Paid</span>
                    <span class="pill" onclick="filterOrders('pending', this)">Pending</span>
                    <span class="pill" onclick="filterOrders('failed', this)">Failed</span>
                </div>
            </div>
            <div class="card-body" id="ordersTable"></div>
        </div>

        <div class="card">
            <div class="card-header"><span>Bot Users (${totalUsers})</span></div>
            <div class="card-body" id="usersTable"></div>
        </div>

        <div class="card">
            <div class="card-header"><span>Broadcast</span></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Target Audience</label>
                    <select class="form-input" id="broadcastTarget" onchange="updateTargetCount()">
                        <option value="all">All Users</option>
                        <option value="active">Active Key Holders</option>
                        <option value="expired">Expired Key Users</option>
                        <option value="no_purchase">Never Purchased</option>
                    </select>
                    <div class="text-dim text-xs" style="margin-top:6px" id="targetCount"></div>
                </div>
                <div class="form-group"><textarea class="form-input" id="broadcastMsg" rows="3" placeholder="Message (Markdown supported)"></textarea></div>
                <div id="broadcastButtons"></div>
                <div style="display:flex;gap:8px;margin-bottom:12px">
                    <button class="btn btn-secondary btn-sm" style="width:auto" onclick="addBroadcastButton()">+ Add Button</button>
                </div>
                <button class="btn btn-primary btn-sm" onclick="sendBroadcast()">Send Broadcast</button>
                <div id="broadcastHistory" style="margin-top:16px"></div>
            </div>
        </div>
    `;

    ['today','week','month','all'].forEach(p => {
        const s = stats[p] || {revenue:0, count:0};
        document.getElementById('rev-' + p).textContent = '\u20B9' + (s.revenue / 100).toLocaleString('en-IN');
        document.getElementById('cnt-' + p).textContent = s.count + ' orders';
    });

    renderPlansTable(plans);
    renderOrdersTable(orders);
    renderUsersTable(users);
    renderBroadcastHistory(broadcasts);
}

async function tgApi(endpoint) {
    const res = await fetch('/features/telegram/api/' + endpoint, { credentials: 'include' });
    if (res.status === 401) { window.location.replace('/login'); return {}; }
    return res.json();
}

async function tgPost(endpoint, data) {
    const res = await fetch('/features/telegram/api/' + endpoint, {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data),
        credentials: 'include'
    });
    return res.json();
}

async function connectBot() {
    const token = document.getElementById('botToken').value.trim();
    if (!token) { toast('Enter bot token', 'error'); return; }
    const res = await tgPost('config.php?action=connect', { bot_token: token });
    if (res.success) { toast('Bot connected: @' + (res.bot?.username || '')); navigate('telegram'); }
    else toast(res.error || 'Failed', 'error');
}

async function disconnectBot() {
    if (!confirm('Disconnect bot?')) return;
    const res = await tgPost('config.php?action=disconnect', {});
    if (res.success) { toast('Bot disconnected'); navigate('telegram'); }
    else toast(res.error || 'Failed', 'error');
}

async function saveRazorpay() {
    const data = {};
    const keyId = document.getElementById('rpKeyId').value.trim();
    const keySecret = document.getElementById('rpKeySecret').value.trim();
    if (keyId) data.rp_key_id = keyId;
    if (keySecret && keySecret !== '\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022') data.rp_key_secret = keySecret;
    const res = await tgPost('config.php?action=save', data);
    if (res.success) { toast('Razorpay saved'); navigate('telegram'); }
    else toast(res.error || 'Failed', 'error');
}

async function saveBotSettings() {
    const res = await tgPost('config.php?action=save', {
        reset_limit_per_day: parseInt(document.getElementById('resetLimit').value),
        reset_cooldown_minutes: parseInt(document.getElementById('resetCooldown').value),
        apk_download_url: document.getElementById('apkUrl').value.trim(),
        setup_video_url: document.getElementById('setupUrl').value.trim()
    });
    if (res.success) toast('Settings saved');
    else toast(res.error || 'Failed', 'error');
}

function renderPlansTable(plans) {
    const t = document.getElementById('plansTable');
    if (!plans.length) { t.innerHTML = '<div class="text-dim">No plans yet</div>'; return; }
    let html = '<table class="table"><thead><tr><th>Name</th><th>Duration</th><th>Price</th><th>Discount</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
    plans.forEach(p => {
        const eff = p.discount_price_paise || p.price_paise;
        const disc = p.discount_price_paise && p.discount_price_paise < p.price_paise ? Math.round((1 - p.discount_price_paise / p.price_paise) * 100) + '%' : '\u2014';
        html += `<tr>
            <td>${p.name}</td><td>${p.duration_days}d</td>
            <td>${p.discount_price_paise ? '<s>\u20B9' + (p.price_paise/100) + '</s> \u20B9' + (eff/100) : '\u20B9' + (p.price_paise/100)}</td>
            <td>${disc}</td>
            <td><span class="badge ${p.is_active ? 'badge-active' : 'badge-expired'}">${p.is_active ? 'Active' : 'Off'}</span></td>
            <td class="actions"><button class="btn-icon" onclick="togglePlan(${p.id})" title="Toggle">&#x23FB;</button><button class="btn-icon" onclick="showPlanModal(${p.id},'${p.name}',${p.duration_days},${p.price_paise},${p.discount_price_paise||0},${p.sort_order||0},'${(p.description||'').replace(/'/g,"\\'")}')" title="Edit">&#x270E;</button><button class="btn-icon" onclick="deletePlan(${p.id})" title="Delete">&#x2715;</button></td>
        </tr>`;
    });
    html += '</tbody></table>';
    t.innerHTML = html;
}

function showPlanModal(id, name, dur, price, disc, sort, desc) {
    showModal(`
        <div class="modal-title">${id ? 'Edit' : 'Add'} Plan</div>
        <div class="form-group"><label class="form-label">Name</label><input class="form-input" id="planName" value="${name||''}"></div>
        <div class="form-group"><label class="form-label">Duration (days)</label><input class="form-input" id="planDur" type="number" value="${dur||''}"></div>
        <div class="form-group"><label class="form-label">Price (\u20B9)</label><input class="form-input" id="planPrice" type="number" value="${price ? price/100 : ''}"></div>
        <div class="form-group"><label class="form-label">Discounted Price (\u20B9) \u2014 leave empty for no discount</label><input class="form-input" id="planDisc" type="number" value="${disc ? disc/100 : ''}"></div>
        <div class="form-group"><label class="form-label">Description</label><input class="form-input" id="planDesc" value="${desc||''}"></div>
        <div class="form-group"><label class="form-label">Sort Order</label><input class="form-input" id="planSort" type="number" value="${sort||0}"></div>
        <div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="savePlan(${id||0})">Save</button></div>
    `);
}

async function savePlan(id) {
    const data = {
        name: document.getElementById('planName').value,
        duration_days: parseInt(document.getElementById('planDur').value),
        price_paise: Math.round(parseFloat(document.getElementById('planPrice').value) * 100),
        discount_price_paise: document.getElementById('planDisc').value ? Math.round(parseFloat(document.getElementById('planDisc').value) * 100) : null,
        description: document.getElementById('planDesc').value,
        sort_order: parseInt(document.getElementById('planSort').value) || 0
    };
    if (id) data.id = id;
    const res = await tgPost('plans.php?action=' + (id ? 'update' : 'create'), data);
    closeModal();
    if (res.success) { toast('Plan saved'); navigate('telegram'); }
    else toast(res.error || 'Failed', 'error');
}

async function togglePlan(id) {
    const res = await tgPost('plans.php?action=toggle', { id });
    if (res.success) navigate('telegram');
}

async function deletePlan(id) {
    if (!confirm('Delete this plan?')) return;
    const res = await tgPost('plans.php?action=delete', { id });
    if (res.success) { toast('Plan deleted'); navigate('telegram'); }
}

function renderOrdersTable(orders) {
    const t = document.getElementById('ordersTable');
    if (!orders.length) { t.innerHTML = '<div class="text-dim">No orders yet</div>'; return; }
    let html = '<table class="table"><thead><tr><th>Date</th><th>User</th><th>Plan</th><th>Amount</th><th>Status</th><th>Key</th></tr></thead><tbody>';
    orders.forEach(o => {
        const st = o.status === 'paid' ? 'badge-active' : (o.status === 'failed' ? 'badge-expired' : 'badge-unused');
        html += `<tr>
            <td class="text-xs">${new Date(o.created_at).toLocaleDateString()}</td>
            <td>${o.username ? '@' + o.username : o.first_name || o.telegram_id}</td>
            <td>${o.plan_name}</td>
            <td>\u20B9${(o.amount_paise/100).toLocaleString()}</td>
            <td><span class="badge ${st}">${o.status}</span></td>
            <td class="text-xs">${o.license_key || '\u2014'}</td>
        </tr>`;
    });
    html += '</tbody></table>';
    t.innerHTML = html;
}

let currentOrderFilter = null;
async function filterOrders(status, el) {
    currentOrderFilter = status;
    document.querySelectorAll('.filter-pills .pill').forEach(p => p.classList.remove('active'));
    el.classList.add('active');
    const url = 'orders.php' + (status ? '?status=' + status : '');
    const res = await tgApi(url);
    renderOrdersTable(res?.orders || []);
}

function renderUsersTable(users) {
    const t = document.getElementById('usersTable');
    if (!users.length) { t.innerHTML = '<div class="text-dim">No users yet</div>'; return; }
    let html = '<table class="table"><thead><tr><th>ID</th><th>Username</th><th>Name</th><th>First Seen</th><th>Orders</th></tr></thead><tbody>';
    users.forEach(u => {
        html += `<tr><td class="text-xs">${u.telegram_id}</td><td>${u.username ? '@' + u.username : '\u2014'}</td><td>${u.first_name || '\u2014'}</td>
        <td class="text-xs">${new Date(u.first_seen).toLocaleDateString()}</td><td>${u.order_count || 0}</td></tr>`;
    });
    html += '</tbody></table>';
    t.innerHTML = html;
}

function addBroadcastButton() {
    const container = document.getElementById('broadcastButtons');
    const idx = container.children.length;
    const row = document.createElement('div');
    row.className = 'row-2col';
    row.style.marginBottom = '8px';
    row.innerHTML = `
        <div class="form-group" style="margin:0"><input class="form-input bc-btn-text" placeholder="Button text" style="font-size:12px"></div>
        <div style="display:flex;gap:4px"><input class="form-input bc-btn-url" placeholder="https://..." style="font-size:12px;flex:1"><button class="btn-icon danger" onclick="this.closest('.row-2col').remove()" style="flex-shrink:0">&times;</button></div>
    `;
    container.appendChild(row);
}

function getBroadcastButtons() {
    const buttons = [];
    document.querySelectorAll('#broadcastButtons .row-2col').forEach(row => {
        const text = row.querySelector('.bc-btn-text').value.trim();
        const url = row.querySelector('.bc-btn-url').value.trim();
        if (text && url) buttons.push({ text, url });
    });
    return buttons;
}

async function sendBroadcast() {
    const msg = document.getElementById('broadcastMsg').value.trim();
    const target = document.getElementById('broadcastTarget').value;
    const buttons = getBroadcastButtons();
    if (!msg) { toast('Enter a message', 'error'); return; }
    const labels = { all: 'ALL', active: 'ACTIVE KEY HOLDERS', expired: 'EXPIRED KEY USERS', no_purchase: 'NON-BUYERS' };
    if (!confirm(`Send this message to ${labels[target]}?`)) return;
    const res = await tgPost('broadcast.php?action=send', { message: msg, target: target, buttons: buttons });
    if (res.success) {
        toast(`Sent to ${res.sent}/${res.total} users`);
        document.getElementById('broadcastMsg').value = '';
        document.getElementById('broadcastButtons').innerHTML = '';
        navigate('telegram');
    } else toast(res.error || 'Failed', 'error');
}

async function updateTargetCount() {
    const target = document.getElementById('broadcastTarget').value;
    const el = document.getElementById('targetCount');
    el.textContent = 'Counting...';
    const res = await tgApi('broadcast.php?action=count&target=' + target);
    if (res.success) el.textContent = res.count + ' user' + (res.count !== 1 ? 's' : '') + ' will receive this message';
    else el.textContent = '';
}

function renderBroadcastHistory(broadcasts) {
    const h = document.getElementById('broadcastHistory');
    if (!broadcasts.length) return;
    const labels = { all: 'All', active: 'Active', expired: 'Expired', no_purchase: 'No Purchase', recent: 'Recent' };
    let html = '<div class="text-dim" style="margin-bottom:8px">Recent Broadcasts</div><table class="table"><thead><tr><th>Date</th><th>Target</th><th>Message</th><th>Sent</th></tr></thead><tbody>';
    broadcasts.slice(0, 10).forEach(b => {
        html += `<tr><td class="text-xs">${new Date(b.sent_at).toLocaleDateString()}</td><td class="text-xs">${labels[b.target_type] || 'All'}</td><td class="text-xs">${b.message.substring(0,50)}${b.message.length>50?'...':''}</td><td>${b.recipient_count}</td></tr>`;
    });
    html += '</tbody></table>';
    h.innerHTML = html;
    updateTargetCount();
}
