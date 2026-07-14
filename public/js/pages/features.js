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
async function featApi(endpoint) {
    const res = await fetch('/features/telegram/api/features.php?action=' + endpoint, { credentials: 'include' });
    if (res.status === 401) { window.location.replace('/login'); return {}; }
    return res.json();
}

async function featPost(action, data) {
    const res = await fetch('/features/telegram/api/features.php?action=' + action, {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data),
        credentials: 'include'
    });
    return res.json();
}

async function renderFeatures(container) {
    const res = await featApi('list');
    const categories = res.categories || {};
    const catNames = Object.keys(categories);
    const totalCount = Object.values(categories).reduce((s, arr) => s + arr.length, 0);

    let html = '<div class="toolbar">' +
        '<div class="text-dim text-xs">' + totalCount + ' function' + (totalCount !== 1 ? 's' : '') + ' across ' + catNames.length + ' categor' + (catNames.length !== 1 ? 'ies' : 'y') + '</div>' +
        '<div style="flex:1"></div>' +
        '<button class="btn btn-secondary btn-sm" onclick="copyApiUrl()">API URL</button>' +
        '<button class="btn btn-primary" onclick="showAddCategory()">+ Category</button>' +
        '<button class="btn btn-primary" onclick="showAddFunction()">+ Function</button>' +
        '</div>';

    if (catNames.length === 0) {
        html += '<div class="card"><div class="empty-state"><div class="empty-art"><svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></div>No functions yet<br><span class="text-dim text-xs">Create a category first, then add functions inside it</span></div></div>';
    } else {
        catNames.forEach(cat => {
            const items = categories[cat];
            const rows = items.map(f => {
                const typeLabel = f.type === 'slider'
                    ? 'slider (' + (f.min_value||0) + '-' + (f.max_value||100) + (f.unit ? f.unit : '') + ')'
                    : 'toggle';
                const defaultLabel = f.type === 'toggle'
                    ? (f.default_value === '1' || f.default_value === 1 ? '<span style="color:var(--accent)">ON</span>' : 'OFF')
                    : f.default_value + (f.unit || '');
                return '<td>' + f.name + '</td>' +
                    '<td class="mono" style="color:var(--accent)">' + f.toggle_id + '</td>' +
                    '<td>' + typeLabel + '</td>' +
                    '<td>' + defaultLabel + '</td>' +
                    '<td>' + badgeHTML(f.is_active == 1 ? 'active' : 'expired') + '</td>' +
                    '<td><div class="actions">' +
                    '<button class="btn-icon" onclick="toggleFeature(' + f.id + ')" title="Toggle Active"><svg viewBox="0 0 24 24"><path d="M18.36 6.64a9 9 0 1 1-12.73 0M12 2v10"/></svg></button>' +
                    '<button class="btn-icon" onclick="showEditFunction(' + f.id + ')" title="Edit"><svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>' +
                    '<button class="btn-icon danger" onclick="deleteFeature(' + f.id + ',\'' + f.name.replace(/'/g, "\\'") + '\')" title="Delete"><svg viewBox="0 0 24 24"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>' +
                    '</div></td>';
            });

            html += '<div class="card" style="margin-bottom:16px"><div style="display:flex;align-items:center;padding:12px 16px;border-bottom:1px solid var(--border)">' +
                '<div style="font-weight:700;font-size:12px;letter-spacing:0.08em;color:var(--accent)">' + cat.toUpperCase() + '</div>' +
                '<div style="flex:1"></div>' +
                '<span class="text-dim text-xs" style="margin-right:12px">' + items.length + ' function' + (items.length !== 1 ? 's' : '') + '</span>' +
                '<button class="btn-icon danger" onclick="deleteCategory(\'' + cat.replace(/'/g, "\\'") + '\')" title="Delete Category"><svg viewBox="0 0 24 24"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>' +
                '</div>' +
                buildTable(['Name', 'Toggle ID', 'Type', 'Default', 'Status', 'Actions'], rows, 'No functions in this category') +
                '</div>';
        });
    }

    container.innerHTML = html;
}

function showAddCategory() {
    showModal(
        '<div class="modal-title">Create Category</div>' +
        '<div class="form-group"><label class="form-label">Category Name</label><input class="form-input" id="catName" placeholder="e.g. AIMBOT, ESP, MISC"></div>' +
        '<p class="text-dim text-xs" style="margin-top:8px">Categories group functions together in the client menu.<br>After creating, add functions inside it.</p>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="createCategory()">Create</button></div>'
    );
}

async function createCategory() {
    const name = document.getElementById('catName').value.trim();
    if (!name) { toast('Enter a category name', 'error'); return; }

    // Create a placeholder toggle to establish the category
    const res = await featPost('create', {
        name: name + ' (placeholder)',
        category: name,
        type: 'toggle',
        toggle_id: Date.now() % 10000 + 100, // temp ID
        default_value: '0',
        is_active: 0
    });
    closeModal();
    if (res.success) {
        // Delete the placeholder immediately
        await featPost('delete', { id: res.id });
        toast('Category "' + name + '" ready — now add functions to it');
        navigate('features');
    } else toast(res.error || 'Failed', 'error');
}

function showAddFunction() {
    // Get existing categories
    featApi('list').then(res => {
        const cats = Object.keys(res.categories || {});
        const catOptions = cats.length > 0
            ? cats.map(c => '<option value="' + c + '">' + c + '</option>').join('')
            : '';

        showModal(
            '<div class="modal-title">Add Function</div>' +
            '<div class="form-group"><label class="form-label">Category</label>' +
                '<input class="form-input" id="fnCategory" list="catList" placeholder="e.g. AIMBOT, ESP, MISC">' +
                '<datalist id="catList">' + catOptions + '</datalist></div>' +
            '<div class="form-group"><label class="form-label">Name</label><input class="form-input" id="fnName" placeholder="e.g. Aimbot, FOV, Enable ESP"></div>' +
            '<div class="form-row"><div class="form-group"><label class="form-label">Toggle ID</label><input class="form-input" id="fnToggleId" type="number" placeholder="e.g. 3"></div>' +
            '<div class="form-group"><label class="form-label">Type</label><select class="form-input" id="fnType" onchange="fnToggleType()"><option value="toggle">Toggle (On/Off)</option><option value="slider">Slider (Range)</option></select></div></div>' +

            '<div id="fnToggleFields"><div class="form-group"><label class="form-label">Default State</label><select class="form-input" id="fnDefaultToggle"><option value="0">OFF</option><option value="1">ON</option></select></div></div>' +

            '<div id="fnSliderFields" style="display:none">' +
            '<div class="form-row"><div class="form-group"><label class="form-label">Min</label><input class="form-input" id="fnMin" type="number" value="0"></div>' +
            '<div class="form-group"><label class="form-label">Max</label><input class="form-input" id="fnMax" type="number" value="100"></div></div>' +
            '<div class="form-row"><div class="form-group"><label class="form-label">Default</label><input class="form-input" id="fnDefault" type="number" value="0"></div>' +
            '<div class="form-group"><label class="form-label">Unit</label><input class="form-input" id="fnUnit" placeholder="e.g. °, m, %" maxlength="5"></div></div>' +
            '</div>' +

            '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="createFunction()">Add Function</button></div>'
        );
    });
}

function fnToggleType() {
    const type = document.getElementById('fnType').value;
    document.getElementById('fnSliderFields').style.display = type === 'slider' ? 'block' : 'none';
    document.getElementById('fnToggleFields').style.display = type === 'toggle' ? 'block' : 'none';
}

async function createFunction() {
    const type = document.getElementById('fnType').value;
    const data = {
        name: document.getElementById('fnName').value.trim(),
        category: document.getElementById('fnCategory').value.trim(),
        type: type,
        toggle_id: parseInt(document.getElementById('fnToggleId').value) || 0
    };

    if (!data.name) { toast('Enter a name', 'error'); return; }
    if (!data.category) { toast('Enter a category', 'error'); return; }
    if (!data.toggle_id) { toast('Enter a toggle ID', 'error'); return; }

    if (type === 'slider') {
        data.min_value = document.getElementById('fnMin').value;
        data.max_value = document.getElementById('fnMax').value;
        data.default_value = document.getElementById('fnDefault').value;
        data.unit = document.getElementById('fnUnit').value.trim();
    } else {
        data.default_value = document.getElementById('fnDefaultToggle').value;
    }

    const res = await featPost('create', data);
    closeModal();
    if (res.success) { toast('Function added'); navigate('features'); }
    else toast(res.error === 'toggle_id_exists' ? 'Toggle ID already in use' : (res.error || 'Failed'), 'error');
}

async function showEditFunction(id) {
    const res = await featApi('list');
    const all = res.features || [];
    const f = all.find(x => x.id == id);
    if (!f) return;

    const isSlider = f.type === 'slider';

    showModal(
        '<div class="modal-title">Edit Function</div>' +
        '<div class="form-group"><label class="form-label">Category</label><input class="form-input" id="eFnCategory" value="' + f.category + '"></div>' +
        '<div class="form-group"><label class="form-label">Name</label><input class="form-input" id="eFnName" value="' + f.name + '"></div>' +
        '<div class="form-row"><div class="form-group"><label class="form-label">Toggle ID</label><input class="form-input" id="eFnToggleId" type="number" value="' + f.toggle_id + '"></div>' +
        '<div class="form-group"><label class="form-label">Type</label><select class="form-input" id="eFnType" onchange="eFnToggleType()"><option value="toggle"' + (f.type === 'toggle' ? ' selected' : '') + '>Toggle</option><option value="slider"' + (f.type === 'slider' ? ' selected' : '') + '>Slider</option></select></div></div>' +

        '<div id="eFnToggleFields" style="display:' + (!isSlider ? 'block' : 'none') + '"><div class="form-group"><label class="form-label">Default</label><select class="form-input" id="eFnDefaultToggle"><option value="0"' + (f.default_value == '0' ? ' selected' : '') + '>OFF</option><option value="1"' + (f.default_value == '1' ? ' selected' : '') + '>ON</option></select></div></div>' +

        '<div id="eFnSliderFields" style="display:' + (isSlider ? 'block' : 'none') + '">' +
        '<div class="form-row"><div class="form-group"><label class="form-label">Min</label><input class="form-input" id="eFnMin" type="number" value="' + (f.min_value || 0) + '"></div>' +
        '<div class="form-group"><label class="form-label">Max</label><input class="form-input" id="eFnMax" type="number" value="' + (f.max_value || 100) + '"></div></div>' +
        '<div class="form-row"><div class="form-group"><label class="form-label">Default</label><input class="form-input" id="eFnDefault" type="number" value="' + f.default_value + '"></div>' +
        '<div class="form-group"><label class="form-label">Unit</label><input class="form-input" id="eFnUnit" value="' + (f.unit || '') + '" maxlength="5"></div></div>' +
        '</div>' +

        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="updateFunction(' + id + ')">Save</button></div>'
    );
}

function eFnToggleType() {
    const type = document.getElementById('eFnType').value;
    document.getElementById('eFnSliderFields').style.display = type === 'slider' ? 'block' : 'none';
    document.getElementById('eFnToggleFields').style.display = type === 'toggle' ? 'block' : 'none';
}

async function updateFunction(id) {
    const type = document.getElementById('eFnType').value;
    const data = {
        id: id,
        name: document.getElementById('eFnName').value.trim(),
        category: document.getElementById('eFnCategory').value.trim(),
        type: type,
        toggle_id: parseInt(document.getElementById('eFnToggleId').value) || 0
    };

    if (type === 'slider') {
        data.min_value = document.getElementById('eFnMin').value;
        data.max_value = document.getElementById('eFnMax').value;
        data.default_value = document.getElementById('eFnDefault').value;
        data.unit = document.getElementById('eFnUnit').value.trim();
    } else {
        data.default_value = document.getElementById('eFnDefaultToggle').value;
    }

    const res = await featPost('update', data);
    closeModal();
    if (res.success) { toast('Function updated'); navigate('features'); }
    else toast(res.error || 'Failed', 'error');
}

async function toggleFeature(id) {
    const res = await featPost('toggle', { id: id });
    if (res.success) navigate('features');
    else toast(res.error || 'Failed', 'error');
}

async function deleteFeature(id, name) {
    confirmAction('Delete "' + name + '"?', 'This will remove it from the client.', 'Delete', async function() {
        const res = await featPost('delete', { id: id });
        if (res.success) { toast('Function deleted'); navigate('features'); }
        else toast(res.error || 'Failed', 'error');
    });
}

async function deleteCategory(cat) {
    confirmAction('Delete category "' + cat + '"?', 'All functions in this category will be deleted.', 'Delete All', async function() {
        const res = await featApi('list');
        const items = (res.categories || {})[cat] || [];
        for (const f of items) {
            await featPost('delete', { id: f.id });
        }
        toast('Category deleted'); navigate('features');
    });
}

function copyApiUrl() {
    const url = window.location.origin + '/api/client_features.php';
    navigator.clipboard.writeText(url).then(() => toast('API URL copied'));
}
