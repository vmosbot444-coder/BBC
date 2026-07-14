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
async function renderSettings(c) {
    if (role !== 'admin') { c.innerHTML = '<div class="empty-state">Admin only</div>'; return; }
    var data = await api('settings');
    if (!data || !data.success) { c.innerHTML = '<div class="empty-state">Failed to load</div>'; return; }

    var s = data.settings;
    var durations = JSON.parse(s.duration_options || '[]');

    c.innerHTML = '<div class="card" style="margin-bottom:12px"><div class="card-body">' +
        '<div class="setting-row"><div><div class="setting-label">Maintenance Mode</div><div class="setting-desc">Block all client auth</div></div><div class="toggle ' + (s.maintenance_mode==='true'?'on':'') + '" onclick="toggleSetting(\'maintenance_mode\',this)"></div></div>' +
        '<div class="setting-row"><div><div class="setting-label">Maintenance Message</div></div><input class="form-input" style="max-width:300px" value="' + (s.maintenance_message||'') + '" onchange="updateSetting(\'maintenance_message\',this.value)"></div>' +
        '<div class="setting-row"><div><div class="setting-label">Devices Per Key</div><div class="setting-desc">Max devices that can use a single key</div></div><input class="form-input" style="max-width:80px;text-align:center" type="number" value="' + (s.default_max_devices||1) + '" min="1" onchange="updateSetting(\'default_max_devices\',this.value)"></div>' +
        '<div class="setting-row"><div><div class="setting-label">Announcement</div><div class="setting-desc">Shown to clients on auth</div></div><input class="form-input" style="max-width:300px" value="' + (s.announcement||'') + '" onchange="updateSetting(\'announcement\',this.value)"></div>' +
        '</div></div>' +
        '<div class="card" style="margin-bottom:12px"><div class="card-header"><div class="card-title">Client Security</div></div><div class="card-body">' +
        '<div class="setting-row"><div><div class="setting-label">Expected APK Hash</div><div class="setting-desc">SHA256 of APK signing certificate</div></div><input class="form-input mono" style="max-width:420px;font-size:11px" value="' + (s.expected_apk_hash||'') + '" placeholder="Leave empty to skip check" onchange="updateSetting(\'expected_apk_hash\',this.value)"></div>' +
        '<div class="setting-row"><div><div class="setting-label">Expected Lib Hash</div><div class="setting-desc">SHA256 of libSMART CHEATclient.so</div></div><input class="form-input mono" style="max-width:420px;font-size:11px" value="' + (s.expected_lib_hash||'') + '" placeholder="Leave empty to skip check" onchange="updateSetting(\'expected_lib_hash\',this.value)"></div>' +
        '</div></div>' +
        '<div class="card" style="margin-bottom:12px"><div class="card-header"><div class="card-title">Duration Options</div><button class="btn btn-primary btn-sm" onclick="showAddDurationModal()">+ Add</button></div><div class="card-body"><div id="durationTags">' +
        durations.map(function(d) { return '<span class="duration-tag">' + d.label + ' <span class="cost">' + d.token_cost + ' tkn</span> <span class="remove" onclick="removeDuration(' + d.days + ')">✕</span></span>'; }).join('') +
        '</div></div></div>' +
        '<div class="card" style="margin-bottom:12px"><div class="card-header"><div class="card-title">Seller Contacts</div><button class="btn btn-primary btn-sm" onclick="showAddSellerContactModal()">+ Add</button></div><div class="card-body"><div id="sellerContactsList">Loading...</div></div></div>' +
        '<div class="danger-zone"><h3>Danger Zone</h3><div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn btn-danger btn-sm" onclick="showAddDaysModal()">+ Add Days to Keys</button><button class="btn btn-danger btn-sm" onclick="showResetAllModal()">Reset All Devices</button><button class="btn btn-danger btn-sm" onclick="confirmAction(\'Delete Expired?\',\'All expired keys removed.\',\'Delete All\',doDeleteExpired)">Delete Expired</button></div></div>';

    loadSellerContacts();
}

async function toggleSetting(key, el) {
    var newVal = el.classList.contains('on') ? 'false' : 'true';
    el.classList.toggle('on');
    await api('settings/update', {setting_key: key, setting_value: newVal});
    toast(key.replace(/_/g,' ') + ' ' + (newVal==='true'?'enabled':'disabled'));
}

async function updateSetting(key, value) {
    await api('settings/update', {setting_key: key, setting_value: value});
    toast('Updated');
}

function showAddDurationModal() {
    showModal(
        '<div class="modal-title">Add Duration</div>' +
        '<div class="form-group"><label class="form-label">Days</label><input class="form-input" id="durDays" type="number" min="1"></div>' +
        '<div class="form-group"><label class="form-label">Label</label><input class="form-input" id="durLabel" placeholder="7 Days"></div>' +
        '<div class="form-group"><label class="form-label">Token Cost</label><input class="form-input" id="durCost" type="number" min="0"></div>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="addDuration()">Add</button></div>'
    );
}

async function addDuration() {
    var res = await api('settings/add-duration', {days: document.getElementById('durDays').value, label: document.getElementById('durLabel').value, token_cost: document.getElementById('durCost').value});
    closeModal();
    if (res.success) { toast('Added'); renderSettings(document.getElementById('content')); } else toast(res.error.replace(/_/g,' '), 'error');
}

async function removeDuration(days) {
    var res = await api('settings/remove-duration', {days: days});
    if (res.success) { toast('Removed'); renderSettings(document.getElementById('content')); } else toast(res.error, 'error');
}

function showAddDaysModal() {
    showModal(
        '<div class="modal-title">Add Days to Keys</div>' +
        '<p style="color:var(--text-secondary);font-size:12px;margin-bottom:14px">Extend expiration for keys. Use this after maintenance or as compensation.</p>' +
        '<div class="form-group"><label class="form-label">Days to Add</label><input class="form-input" id="addDaysCount" type="number" min="1" value="1"></div>' +
        '<div class="form-group"><label class="form-label">Apply To</label><div class="radio-group" id="addDaysFilter"><div class="radio-option selected" onclick="selectFilter(this,\'active\')">Active Keys Only</div><div class="radio-option" onclick="selectFilter(this,\'all\')">All Keys</div></div></div>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="doAddDays()">Add Days</button></div>'
    );
}

var addDaysFilterVal = 'active';
function selectFilter(el, val) {
    addDaysFilterVal = val;
    el.parentElement.querySelectorAll('.radio-option').forEach(function(o) { o.classList.remove('selected'); });
    el.classList.add('selected');
}

async function doAddDays() {
    var days = document.getElementById('addDaysCount').value;
    var res = await api('keys/add-days', {days: days, filter: addDaysFilterVal});
    closeModal();
    if (res.success) { toast(res.affected + ' keys extended by ' + days + ' day(s)'); } else toast(res.error, 'error');
}

async function loadSellerContacts() {
    var data = await api('client/sellers');
    var el = document.getElementById('sellerContactsList');
    if (!el) return;
    if (!data || !data.success || !data.contacts.length) {
        el.innerHTML = '<div class="empty-state" style="padding:10px">No contacts added</div>';
        return;
    }
    el.innerHTML = data.contacts.map(function(c, i) {
        var badges = '';
        if (c.telegram) badges += '<span class="badge badge-active" style="font-size:10px">Telegram</span>';
        if (c.whatsapp) badges += '<span class="badge badge-active" style="font-size:10px;margin-left:4px">WhatsApp</span>';
        return '<div class="log-item"><span class="mono">' + c.name + '</span>' + badges + '<span class="remove" style="margin-left:auto;cursor:pointer;color:var(--danger)" onclick="removeSellerContact(' + i + ')">✕</span></div>';
    }).join('');
}

function showAddSellerContactModal() {
    showModal(
        '<div class="modal-title">Add Seller Contact</div>' +
        '<div class="form-group"><label class="form-label">Name</label><input class="form-input" id="scName" placeholder="Seller name"></div>' +
        '<div class="form-group"><label class="form-label">Telegram Link</label><input class="form-input" id="scTelegram" placeholder="https://t.me/username (optional)"></div>' +
        '<div class="form-group"><label class="form-label">WhatsApp Link</label><input class="form-input" id="scWhatsapp" placeholder="https://wa.me/91XXXXXXXX (optional)"></div>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="addSellerContact()">Add</button></div>'
    );
}

async function addSellerContact() {
    var res = await api('settings/add-seller-contact', {name: document.getElementById('scName').value, telegram: document.getElementById('scTelegram').value, whatsapp: document.getElementById('scWhatsapp').value});
    closeModal();
    if (res.success) { toast('Contact added'); loadSellerContacts(); } else toast(res.error.replace(/_/g,' '), 'error');
}

async function removeSellerContact(index) {
    var res = await api('settings/remove-seller-contact', {index: index});
    if (res.success) { toast('Removed'); loadSellerContacts(); } else toast(res.error, 'error');
}
