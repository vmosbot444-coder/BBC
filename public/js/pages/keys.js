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
async function renderKeys(c, page, status, search) {
    page = page || 1;
    status = status || '';
    search = search || '';

    var params = '?page=' + page + '&status=' + status + '&search=' + encodeURIComponent(search);
    var data = await api('keys' + params);
    if (!data || !data.success) { c.innerHTML = '<div class="empty-state"><div class="empty-art">#</div>Failed to load keys</div>'; return; }

    var settingsData = await api('settings');
    if (settingsData && settingsData.success) {
        durationOptions = settingsData.settings ? JSON.parse(settingsData.settings.duration_options || '[]') : settingsData.duration_options || [];
    }

    var rows = data.keys.map(function(k) {
        return '<td class="mono">' + k.license_key + '</td>' +
            '<td>' + badgeHTML(k.status) + '</td>' +
            '<td>' + k.duration_days + 'd</td>' +
            '<td class="mono">' + (k.expires_at ? new Date(k.expires_at).toLocaleDateString() : '—') + '</td>' +
            '<td>' + k.device_count + '/' + k.max_devices + '</td>' +
            '<td class="mono">' + truncate(k.hwids) + '</td>' +
            '<td><div class="actions">' +
            '<button class="btn-icon" onclick="resetDevice(' + k.id + ')" title="Reset Device">↻</button>' +
            (role === 'admin' ? '<button class="btn-icon" onclick="editKeyModal(' + k.id + ',' + k.max_devices + ',' + k.duration_days + ',\'' + k.status + '\')" title="Edit">✎</button>' +
            '<button class="btn-icon ' + (k.status==='banned'?'':'danger') + '" onclick="' + (k.status==='banned'?'unbanKey('+k.id+')':'banKey('+k.id+')') + '" title="' + (k.status==='banned'?'Unban':'Ban') + '">' + (k.status==='banned'?'✓':'⊘') + '</button>' +
            '<button class="btn-icon danger" onclick="confirmAction(\'Delete Key?\',\'This cannot be undone.\',\'Delete\',function(){deleteKey(' + k.id + ')})" title="Delete">✕</button>' : '') +
            '</div></td>';
    });

    c.innerHTML = '<div class="toolbar">' +
        '<input class="search-input" id="keySearch" placeholder="Search key or note..." value="' + search + '" onkeydown="if(event.key===\'Enter\')filterKeys()">' +
        '<select id="keyStatus" onchange="filterKeys()">' +
        '<option value="">All Status</option><option value="active"' + (status==='active'?' selected':'') + '>Active</option><option value="unused"' + (status==='unused'?' selected':'') + '>Unused</option><option value="expired"' + (status==='expired'?' selected':'') + '>Expired</option><option value="banned"' + (status==='banned'?' selected':'') + '>Banned</option></select>' +
        '<div style="flex:1"></div>' +
        '<button class="btn btn-primary" onclick="showGenerateModal()">+ Generate</button>' +
        (role === 'admin' ? '<button class="btn btn-danger btn-sm" onclick="confirmAction(\'Delete Expired?\',\'All expired keys will be permanently removed.\',\'Delete All\',doDeleteExpired)">Delete Expired</button><button class="btn btn-secondary btn-sm" onclick="showResetAllModal()">Reset All</button>' : '') +
        '</div><div class="card">' +
        buildTable(['Key','Status','Duration','Expires','Devices','HWID','Actions'], rows, 'No keys found') +
        pagination(page, data.pages, 'loadKeysPage') + '</div>';
}

function loadKeysPage(p) { renderKeys(document.getElementById('content'), p, document.getElementById('keyStatus').value, document.getElementById('keySearch').value); }
function filterKeys() { loadKeysPage(1); }

var sellerTokens = 0;

function showGenerateModal() {
    var tokenInfo = '';
    if (role === 'seller') {
        tokenInfo = '<div class="token-meter" id="tokenMeter"><div class="token-meter-row"><span>Balance</span><span class="mono" id="genBalance">' + sellerTokens + ' tokens</span></div><div class="token-meter-row"><span>Cost</span><span class="mono" id="genCost">—</span></div><div class="token-meter-bar"><div class="token-meter-fill" id="genBarFill"></div></div><div class="token-meter-row"><span>After</span><span class="mono" id="genAfter">—</span></div></div>';
    }
    showModal(
        '<div class="modal-title">Generate Keys</div>' +
        tokenInfo +
        '<div class="form-group"><label class="form-label">Count</label><input class="form-input" id="genCount" type="number" value="1" min="1" max="100" oninput="updateGenCost()"></div>' +
        '<div class="form-group"><label class="form-label">Duration</label><select class="form-input" id="genDuration" onchange="updateGenCost()">' +
        durationOptions.map(function(o) { return '<option value="' + o.days + '" data-cost="' + o.token_cost + '">' + o.label + ' — ' + o.token_cost + ' token' + (o.token_cost > 1 ? 's' : '') + ' each</option>'; }).join('') +
        '</select></div>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" id="genBtn" onclick="generateKeys()">Generate</button></div>'
    );
    updateGenCost();
}

function updateGenCost() {
    if (role !== 'seller') return;
    var count = parseInt(document.getElementById('genCount').value) || 0;
    var sel = document.getElementById('genDuration');
    var cost = parseInt(sel.options[sel.selectedIndex]?.dataset.cost) || 0;
    var total = count * cost;
    var after = sellerTokens - total;
    document.getElementById('genCost').textContent = total + ' tokens';
    document.getElementById('genCost').style.color = total > sellerTokens ? 'var(--danger)' : 'var(--accent)';
    document.getElementById('genAfter').textContent = after + ' tokens';
    document.getElementById('genAfter').style.color = after < 0 ? 'var(--danger)' : 'var(--text-secondary)';
    var pct = sellerTokens > 0 ? Math.min(100, Math.max(0, ((sellerTokens - total) / sellerTokens) * 100)) : 0;
    document.getElementById('genBarFill').style.width = pct + '%';
    document.getElementById('genBarFill').style.background = after < 0 ? 'var(--danger)' : 'var(--accent)';
    document.getElementById('genBtn').disabled = after < 0;
    document.getElementById('genBtn').style.opacity = after < 0 ? '0.4' : '1';
}

async function generateKeys() {
    var res = await api('keys/generate', {count: document.getElementById('genCount').value, duration_days: document.getElementById('genDuration').value});
    closeModal();
    if (res.success) {
        toast(res.count + ' key(s) generated');
        showModal('<div class="modal-title">Generated Keys</div><div style="max-height:300px;overflow-y:auto">' + res.keys.map(function(k){return '<div class="mono" style="padding:4px 0;font-size:12px;color:var(--accent)">'+k+'</div>';}).join('') + '</div><div class="modal-actions"><button class="btn btn-primary" onclick="navigator.clipboard.writeText(\'' + res.keys.join('\\n') + '\');toast(\'Copied\')">Copy All</button><button class="btn btn-secondary" onclick="closeModal();filterKeys()">Close</button></div>');
    } else toast(res.error.replace(/_/g,' '), 'error');
}

async function resetDevice(id) {
    var res = await api('keys/reset-device', {id: id});
    if (res.success) { toast('Device reset'); filterKeys(); } else toast(res.error, 'error');
}

async function banKey(id) {
    var res = await api('keys/ban', {id: id});
    if (res.success) { toast('Key banned'); filterKeys(); } else toast(res.error, 'error');
}

async function unbanKey(id) {
    var res = await api('keys/unban', {id: id});
    if (res.success) { toast('Key unbanned'); filterKeys(); } else toast(res.error, 'error');
}

async function deleteKey(id) {
    var res = await api('keys/delete', {id: id});
    if (res.success) { toast('Key deleted'); filterKeys(); } else toast(res.error, 'error');
}

function editKeyModal(id, maxDevices, duration, status) {
    showModal(
        '<div class="modal-title">Edit Key</div>' +
        '<div class="form-row"><div class="form-group"><label class="form-label">Max Devices</label><input class="form-input" id="editMaxDev" type="number" value="' + maxDevices + '" min="1"></div><div class="form-group"><label class="form-label">Duration (days)</label><input class="form-input" id="editDuration" type="number" value="' + duration + '"></div></div>' +
        '<div class="form-group"><label class="form-label">Status</label><select class="form-input" id="editStatus"><option value="active"' + (status==='active'?' selected':'') + '>Active</option><option value="unused"' + (status==='unused'?' selected':'') + '>Unused</option><option value="expired"' + (status==='expired'?' selected':'') + '>Expired</option><option value="banned"' + (status==='banned'?' selected':'') + '>Banned</option></select></div>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="saveKey(' + id + ')">Save</button></div>'
    );
}

async function saveKey(id) {
    var res = await api('keys/edit', {id: id, max_devices: document.getElementById('editMaxDev').value, duration_days: document.getElementById('editDuration').value, status: document.getElementById('editStatus').value});
    closeModal();
    if (res.success) { toast('Key updated'); filterKeys(); } else toast(res.error, 'error');
}

async function doDeleteExpired() {
    var res = await api('keys/delete-expired', {});
    if (res.success) { toast(res.deleted + ' expired keys deleted'); filterKeys(); } else toast(res.error, 'error');
}

function showResetAllModal() {
    showModal(
        '<div class="modal-title">Reset ALL Devices?</div>' +
        '<p style="color:var(--text-secondary);font-size:13px;margin-bottom:12px">Every device registration will be cleared.</p>' +
        '<div class="form-group"><label class="form-label">Type RESET to confirm</label><input class="form-input" id="resetConfirm" placeholder="RESET"></div>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-danger" onclick="doResetAll()">Reset All</button></div>',
        true
    );
}

async function doResetAll() {
    if (document.getElementById('resetConfirm').value !== 'RESET') { toast('Type RESET to confirm', 'error'); return; }
    var res = await api('keys/reset-all', {});
    closeModal();
    if (res.success) { toast('All devices reset'); filterKeys(); } else toast(res.error, 'error');
}
