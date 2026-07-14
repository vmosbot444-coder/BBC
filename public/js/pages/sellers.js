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
async function renderSellers(c) {
    if (role !== 'admin') { c.innerHTML = '<div class="empty-state">Admin only</div>'; return; }
    var data = await api('sellers');
    if (!data || !data.success) { c.innerHTML = '<div class="empty-state">Failed to load</div>'; return; }

    var rows = data.sellers.map(function(s) {
        var charged = Number(s.total_charged || 0);
        var paid = Number(s.total_paid || 0);
        var due = charged - paid;
        var dueStyle = due > 0 ? 'color:var(--danger);font-weight:600' : '';
        var paidStyle = paid > 0 ? 'color:var(--accent);font-weight:600' : '';
        return '<td class="mono">' + s.username + '</td>' +
            '<td class="mono" style="color:var(--accent)">' + s.tokens + '</td>' +
            '<td class="mono">' + s.total_earned + '</td><td class="mono">' + s.total_spent + '</td>' +
            '<td class="mono" style="' + paidStyle + '">' + (paid > 0 ? '₹' + paid.toLocaleString('en-IN') : '—') + '</td>' +
            '<td class="mono" style="' + dueStyle + '">' + (due > 0 ? '₹' + due.toLocaleString('en-IN') : '—') + '</td>' +
            '<td>' + s.key_count + '</td>' +
            '<td>' + (s.is_active == 1 ? badgeHTML('active') : badgeHTML('banned')) + '</td>' +
            '<td class="mono">' + (s.last_login ? new Date(s.last_login).toLocaleDateString() : '—') + '</td>' +
            '<td><div class="actions">' +
            '<button class="btn-icon" onclick="showAddTokensModal(' + s.id + ',\'' + s.username + '\')" title="Add Tokens">+</button>' +
            '<button class="btn-icon" onclick="showRemoveTokensModal(' + s.id + ',\'' + s.username + '\',' + s.tokens + ')" title="Remove Tokens">−</button>' +
            '<button class="btn-icon" onclick="showPaymentHistory(' + s.id + ',\'' + s.username + '\')" title="Payments">₹</button>' +
            '<button class="btn-icon" onclick="showSellerKeys(' + s.id + ',\'' + s.username + '\')" title="Keys">#</button>' +
            '<button class="btn-icon" onclick="editSellerModal(' + s.id + ',\'' + s.username + '\')" title="Edit">✎</button>' +
            '<button class="btn-icon" onclick="toggleSeller(' + s.id + ')" title="Toggle">' + (s.is_active == 1 ? '⊘' : '✓') + '</button>' +
            '<button class="btn-icon danger" onclick="confirmAction(\'Delete ' + s.username + '?\',\'This removes the seller and transactions.\',\'Delete\',function(){doDeleteSeller(' + s.id + ')})" title="Delete">✕</button>' +
            '</div></td>';
    });

    c.innerHTML = '<div class="toolbar"><div style="flex:1"></div><button class="btn btn-primary" onclick="showCreateSellerModal()">+ Create Seller</button></div>' +
        '<div class="card">' + buildTable(['Username','Tokens','Earned','Spent','Paid','Due','Keys','Status','Last Login','Actions'], rows, 'No sellers') + '</div>';
}

function showCreateSellerModal() {
    showModal(
        '<div class="modal-title">Create Seller</div>' +
        '<div class="form-group"><label class="form-label">Username</label><input class="form-input" id="sellerUser" placeholder="seller_name"></div>' +
        '<div class="form-group"><label class="form-label">Password</label><input class="form-input" id="sellerPass" type="password" placeholder="min 6 chars"></div>' +
        '<div class="form-group"><label class="form-label">Initial Tokens</label><input class="form-input" id="sellerTokens" type="number" value="0" min="0"></div>' +
        '<div class="form-row"><div class="form-group"><label class="form-label">Price (₹)</label><input class="form-input" id="sellerAmount" type="number" value="0" step="1"></div><div class="form-group"><label class="form-label">Paid Now (₹)</label><input class="form-input" id="sellerPaid" type="number" value="0" step="1"></div></div>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="createSeller()">Create</button></div>'
    );
}

async function createSeller() {
    var res = await api('sellers/create', {
        username: document.getElementById('sellerUser').value,
        password: document.getElementById('sellerPass').value,
        tokens: document.getElementById('sellerTokens').value,
        amount: document.getElementById('sellerAmount').value,
        paid_now: document.getElementById('sellerPaid').value
    });
    closeModal();
    if (res.success) { toast('Seller created'); renderSellers(document.getElementById('content')); } else toast(res.error.replace(/_/g,' '), 'error');
}

function showAddTokensModal(id, username) {
    showModal(
        '<div class="modal-title">Add Tokens — ' + username + '</div>' +
        '<div class="form-group"><label class="form-label">Tokens</label><input class="form-input" id="addTkCount" type="number" value="10" min="1"></div>' +
        '<div class="form-group"><label class="form-label">Price (₹)</label><input class="form-input" id="addTkAmount" type="number" value="0" step="1"></div>' +
        '<div class="form-group"><label class="form-label">Note</label><input class="form-input" id="addTkNote" placeholder="optional"></div>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="addTokens(' + id + ')">Add</button></div>'
    );
}

async function addTokens(id) {
    var res = await api('sellers/add-tokens', {
        id: id,
        tokens: document.getElementById('addTkCount').value,
        amount: document.getElementById('addTkAmount').value,
        note: document.getElementById('addTkNote').value
    });
    closeModal();
    if (res.success) { toast('Tokens added'); renderSellers(document.getElementById('content')); } else toast(res.error.replace(/_/g,' '), 'error');
}

function showRemoveTokensModal(id, username, currentTokens) {
    showModal(
        '<div class="modal-title">Remove Tokens — ' + username + '</div>' +
        '<div class="form-group"><label class="form-label">Current: ' + currentTokens + ' tokens</label><input class="form-input" id="rmTkCount" type="number" value="1" min="1" max="' + currentTokens + '"></div>' +
        '<div class="form-group"><label class="form-label">Note</label><input class="form-input" id="rmTkNote" placeholder="optional"></div>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" style="background:var(--danger)" onclick="removeTokens(' + id + ')">Remove</button></div>'
    );
}

async function removeTokens(id) {
    var res = await api('sellers/remove-tokens', {
        id: id,
        tokens: document.getElementById('rmTkCount').value,
        note: document.getElementById('rmTkNote').value
    });
    closeModal();
    if (res.success) { toast('Tokens removed'); renderSellers(document.getElementById('content')); } else toast(res.error.replace(/_/g,' '), 'error');
}

async function showPaymentHistory(id, username) {
    var data = await api('sellers/transactions?id=' + id);
    if (!data || !data.success) { toast('Failed', 'error'); return; }

    var charged = Number(data.total_charged || 0);
    var paid = Number(data.total_paid || 0);
    var due = charged - paid;

    var summaryHtml = '<div style="display:flex;gap:12px;margin-bottom:16px">' +
        '<div style="flex:1;background:var(--bg-tertiary);padding:10px 14px;border-radius:8px;text-align:center"><div style="font-size:10px;color:var(--text-muted);margin-bottom:4px">CHARGED</div><div class="mono" style="font-size:16px">₹' + charged.toLocaleString('en-IN') + '</div></div>' +
        '<div style="flex:1;background:var(--bg-tertiary);padding:10px 14px;border-radius:8px;text-align:center"><div style="font-size:10px;color:var(--text-muted);margin-bottom:4px">PAID</div><div class="mono" style="font-size:16px;color:var(--accent)">₹' + paid.toLocaleString('en-IN') + '</div></div>' +
        '<div style="flex:1;background:var(--bg-tertiary);padding:10px 14px;border-radius:8px;text-align:center"><div style="font-size:10px;color:var(--text-muted);margin-bottom:4px">DUE</div><div class="mono" style="font-size:16px;color:' + (due > 0 ? 'var(--danger)' : 'var(--accent)') + '">₹' + due.toLocaleString('en-IN') + '</div></div></div>';

    var paySection = due > 0 ? '<div style="display:flex;gap:8px;margin-bottom:16px"><input class="form-input" id="payAmount" type="number" value="' + due + '" step="1" placeholder="Amount" style="flex:1"><input class="form-input" id="payNote" placeholder="Note" style="flex:1"><button class="btn btn-primary" onclick="doRecordPayment(' + id + ',\'' + username + '\')">Pay</button></div>' : '';

    var rows = data.transactions.map(function(t) {
        var amt = Number(t.amount || 0);
        var tkn = Number(t.tokens || 0);
        var icon = '', color = '', label = '';
        switch (t.type) {
            case 'refill': icon = '+'; color = 'var(--accent)'; label = '+' + tkn + ' tkn'; break;
            case 'spend': icon = '−'; color = 'var(--danger)'; label = '−' + tkn + ' tkn'; break;
            case 'deduct': icon = '↓'; color = '#FBBF24'; label = '−' + tkn + ' tkn'; break;
            case 'payment': icon = '₹'; color = 'var(--accent)'; label = ''; break;
        }
        return '<div class="log-item">' +
            '<span class="log-icon" style="color:' + color + '">' + icon + '</span>' +
            '<span class="badge badge-' + t.type + '">' + t.type + '</span>' +
            (label ? '<span class="mono" style="color:' + color + '">' + label + '</span>' : '') +
            (amt > 0 ? '<span class="mono">' + (t.type === 'payment' ? '<span style="color:var(--accent)">+₹' : '₹') + amt.toLocaleString('en-IN') + (t.type === 'payment' ? '</span>' : '') + '</span>' : '') +
            (t.note ? '<span style="color:var(--text-muted);font-size:11px">' + t.note + '</span>' : '') +
            '<span style="color:var(--text-muted);font-size:11px;margin-left:auto">' + new Date(t.created_at).toLocaleDateString() + '</span></div>';
    }).join('');

    showModal(
        '<div class="modal-title">Payments — ' + username + '</div>' +
        summaryHtml + paySection +
        '<div style="max-height:300px;overflow-y:auto">' + (rows || '<div class="empty-state">No transactions</div>') + '</div>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Close</button></div>'
    );
}

async function doRecordPayment(id, username) {
    var amount = document.getElementById('payAmount').value;
    var note = document.getElementById('payNote')?.value || '';
    var res = await api('sellers/record-payment', { id: id, amount: amount, note: note });
    closeModal();
    if (res.success) { toast('Payment recorded'); renderSellers(document.getElementById('content')); showPaymentHistory(id, username); } else toast(res.error.replace(/_/g,' '), 'error');
}

async function showSellerKeys(id, username) {
    var data = await api('sellers/keys?id=' + id);
    if (!data || !data.success) { toast('Failed', 'error'); return; }
    var rows = data.keys.map(function(k) { return '<div class="log-item"><span class="mono" style="font-size:11px">' + k.license_key + '</span>' + badgeHTML(k.status) + '<span class="mono" style="margin-left:auto">' + k.duration_days + 'd</span></div>'; }).join('');
    showModal('<div class="modal-title">Keys by ' + username + '</div><div style="max-height:400px;overflow-y:auto">' + (rows || '<div class="empty-state">None</div>') + '</div><div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Close</button></div>');
}

function editSellerModal(id, username) {
    showModal(
        '<div class="modal-title">Edit — ' + username + '</div>' +
        '<div class="form-group"><label class="form-label">Username</label><input class="form-input" id="editSellerUser" value="' + username + '"></div>' +
        '<div class="form-group"><label class="form-label">New Password (blank = keep)</label><input class="form-input" id="editSellerPass" type="password"></div>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="saveSeller(' + id + ')">Save</button></div>'
    );
}

async function saveSeller(id) {
    var d = {id: id, username: document.getElementById('editSellerUser').value};
    var pw = document.getElementById('editSellerPass').value;
    if (pw) d.password = pw;
    var res = await api('sellers/edit', d);
    closeModal();
    if (res.success) { toast('Updated'); renderSellers(document.getElementById('content')); } else toast(res.error.replace(/_/g,' '), 'error');
}

async function toggleSeller(id) {
    var res = await api('sellers/toggle', {id: id});
    if (res.success) { toast('Toggled'); renderSellers(document.getElementById('content')); } else toast(res.error, 'error');
}

async function doDeleteSeller(id) {
    var res = await api('sellers/delete', {id: id});
    if (res.success) { toast('Deleted'); renderSellers(document.getElementById('content')); } else toast(res.error, 'error');
}
