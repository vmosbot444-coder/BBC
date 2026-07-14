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
let role = '';
let username = '';
let currentPage = 'dashboard';
let durationOptions = [];

async function api(endpoint, data = null) {
    const opts = {};
    if (data) {
        opts.method = 'POST';
        if (data instanceof FormData) {
            opts.body = data;
        } else {
            opts.body = new FormData();
            Object.entries(data).forEach(([k, v]) => opts.body.append(k, v));
        }
    }
    const res = await fetch('/api/' + endpoint, opts);
    if (res.status === 401) { window.location.replace('/login'); return; }
    return res.json();
}

function toast(msg, type = 'success') {
    const c = document.getElementById('toasts');
    const d = document.createElement('div');
    d.className = 'toast toast-' + type;
    d.textContent = msg;
    c.appendChild(d);
    setTimeout(() => d.remove(), 3000);
}

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('mobileOverlay').classList.toggle('show');
}

async function logout() {
    await api('logout', {});
    window.location.replace('/login');
}

function navigate(page) {
    currentPage = page;
    document.querySelectorAll('.nav-item').forEach(n => n.classList.toggle('active', n.dataset.page === page));
    document.getElementById('topbarTitle').innerHTML = '<span>' + page.charAt(0).toUpperCase() + page.slice(1) + '</span>';
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('mobileOverlay').classList.remove('show');
    var el = document.getElementById('content');
    el.style.animation = 'none';
    el.offsetHeight;
    el.style.animation = '';
    loadPage(page);
}

async function loadPage(page) {
    const c = document.getElementById('content');
    c.innerHTML = '<div class="skel-row"><div class="skel-card skeleton"></div><div class="skel-card skeleton"></div><div class="skel-card skeleton"></div><div class="skel-card skeleton"></div></div>' +
        '<div class="skel-row"><div class="skel-block skeleton" style="flex:1"></div><div class="skel-block skeleton" style="flex:1"></div></div>' +
        '<div class="skel-bar skeleton w75"></div><div class="skel-bar skeleton w50"></div><div class="skel-bar skeleton w30"></div>';
    switch (page) {
        case 'dashboard': await renderDashboard(c); break;
        case 'keys': await renderKeys(c); break;
        case 'sellers': await renderSellers(c); break;
        case 'files': await renderFiles(c); break;
        case 'logs': await renderLogs(c); break;
        case 'settings': await renderSettings(c); break;
        case 'telegram': await renderTelegram(c); break;
        case 'features': await renderFeatures(c); break;
    }
}

function animateCount(el, target) {
    let current = 0;
    const step = Math.max(1, Math.ceil(target / 30));
    const interval = setInterval(() => {
        current += step;
        if (current >= target) { current = target; clearInterval(interval); }
        el.textContent = current >= 1000 ? current.toLocaleString('en-IN') : current;
    }, 20);
}

function badgeHTML(status) {
    return '<span class="badge badge-' + status + '"><span class="dot dot-' + status + '"></span>' + status + '</span>';
}

function truncate(str, len) {
    len = len || 12;
    if (!str) return '—';
    return str.length > len ? str.substring(0, len) + '...' : str;
}

async function init() {
    var brandName = document.title.split(' ')[0];
    var sidebarLogo = document.getElementById('sidebarLogo');
    if (sidebarLogo) sidebarLogo.textContent = brandName[0];
    try {
        const res = await fetch('/api/dashboard');
        if (res.status === 401) { window.location.replace('/login'); return; }
        const data = await res.json();
        if (!data.success) { window.location.replace('/login'); return; }

        role = data.role;
        username = data.username;
        document.getElementById('userRole').textContent = role;
        document.getElementById('userName').textContent = username;

        if (data.maintenance) {
            var dot = document.getElementById('statusDot');
            dot.classList.add('offline');
            dot.title = 'Maintenance Mode';
        }

        if (role === 'seller') {
            sellerTokens = data.tokens || 0;
            ['nav-sellers', 'nav-files', 'nav-logs', 'nav-settings', 'nav-telegram', 'nav-features'].forEach(function(id) {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
        }

        renderDashboard(document.getElementById('content'));
    } catch (e) {
        window.location.replace('/login');
    }
}

function showAccountModal() {
    var usernameField = '';
    if (role === 'admin') {
        usernameField = '<div class="form-group"><label class="form-label">New Username</label><input class="form-input" id="accUsername" value="' + username + '" placeholder="leave unchanged"></div>';
    }
    showModal(
        '<div class="modal-title">Account Settings</div>' +
        usernameField +
        '<div class="form-group"><label class="form-label">Current Password</label><input class="form-input" id="accCurrentPass" type="password" placeholder="required to save changes"></div>' +
        '<div class="form-group"><label class="form-label">New Password</label><input class="form-input" id="accNewPass" type="password" placeholder="leave blank to keep current"></div>' +
        '<div class="form-group"><label class="form-label">Confirm New Password</label><input class="form-input" id="accConfirmPass" type="password" placeholder="••••••••"></div>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="saveAccount()">Save</button></div>'
    );
}

async function saveAccount() {
    var currentPass = document.getElementById('accCurrentPass').value;
    var newPass = document.getElementById('accNewPass').value;
    var confirmPass = document.getElementById('accConfirmPass').value;

    if (!currentPass) { toast('Enter current password', 'error'); return; }
    if (newPass && newPass.length < 6) { toast('New password min 6 chars', 'error'); return; }
    if (newPass && newPass !== confirmPass) { toast('Passwords do not match', 'error'); return; }

    var payload = {current_password: currentPass};
    if (newPass) payload.new_password = newPass;

    var usernameEl = document.getElementById('accUsername');
    if (usernameEl && usernameEl.value !== username) {
        payload.new_username = usernameEl.value;
    }

    var res = await api('account/update', payload);
    closeModal();
    if (res.success) {
        toast('Account updated');
        if (payload.new_username) {
            username = payload.new_username;
            document.getElementById('userName').textContent = username;
        }
    } else {
        toast(res.error.replace(/_/g, ' '), 'error');
    }
}
