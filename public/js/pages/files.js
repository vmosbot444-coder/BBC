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
async function renderFiles(c) {
    if (role !== 'admin') { c.innerHTML = '<div class="empty-state">Admin only</div>'; return; }
    var data = await api('files');
    if (!data || !data.success) { c.innerHTML = '<div class="empty-state">Failed to load</div>'; return; }

    var archs = ['arm64-v8a', 'x86_64'];
    var html = '';
    archs.forEach(function(arch) {
        var files = data.files.filter(function(f) { return f.arch === arch; });
        var active = files.find(function(f) { return f.is_active == 1; });
        html += '<div class="file-card"><div class="file-card-header"><span class="file-arch">' + arch + '</span><button class="btn btn-primary btn-sm" onclick="showUploadModal(\'' + arch + '\')">Upload</button></div>' +
            (active ? '<div class="file-meta" style="margin-bottom:12px"><span>v' + active.version + '</span><span>' + active.file_size_formatted + '</span><span>' + new Date(active.uploaded_at).toLocaleDateString() + '</span></div>' : '<div style="color:var(--text-muted);font-size:12px;margin-bottom:12px">No file uploaded</div>') +
            (files.length > 1 ? '<div style="font-size:11px;color:var(--text-muted);margin-bottom:8px">History</div>' + files.map(function(f) { return '<div class="log-item"><span class="mono">v' + f.version + '</span><span class="mono">' + f.file_size_formatted + '</span>' + (f.is_active == 1 ? '<span class="badge badge-active">active</span>' : '<button class="btn btn-sm btn-secondary" onclick="setActiveFile(' + f.id + ')">Activate</button>') + '<button class="btn-icon danger" style="margin-left:auto" onclick="confirmAction(\'Delete?\',\'Remove this version.\',\'Delete\',function(){doDeleteFile(' + f.id + ')})" title="Delete">✕</button></div>'; }).join('') : '') +
            '</div>';
    });
    c.innerHTML = html;
}

function showUploadModal(arch) {
    showModal(
        '<div class="modal-title">Upload — ' + arch + '</div>' +
        '<div class="form-group"><label class="form-label">Version</label><input class="form-input" id="uploadVersion" placeholder="e.g. 2.1"></div>' +
        '<div class="form-group"><label class="form-label">File</label>' +
        '<div class="upload-zone" id="uploadZone" onclick="document.getElementById(\'uploadFile\').click()" ondragover="event.preventDefault();this.classList.add(\'dragover\')" ondragleave="this.classList.remove(\'dragover\')" ondrop="event.preventDefault();this.classList.remove(\'dragover\');document.getElementById(\'uploadFile\').files=event.dataTransfer.files;this.textContent=event.dataTransfer.files[0].name">Click or drag file</div>' +
        '<input type="file" id="uploadFile" style="display:none" onchange="document.getElementById(\'uploadZone\').textContent=this.files[0]?.name||\'Click or drag file\'"></div>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="doUpload(\'' + arch + '\')">Upload</button></div>'
    );
}

async function doUpload(arch) {
    var file = document.getElementById('uploadFile').files[0];
    var version = document.getElementById('uploadVersion').value;
    if (!file || !version) { toast('Select file and version', 'error'); return; }
    var fd = new FormData();
    fd.append('arch', arch);
    fd.append('version', version);
    fd.append('file', file);
    var res = await api('files/upload', fd);
    closeModal();
    if (res.success) { toast('Uploaded'); renderFiles(document.getElementById('content')); } else toast(res.error, 'error');
}

async function setActiveFile(id) {
    var res = await api('files/set-active', {id: id});
    if (res.success) { toast('Activated'); renderFiles(document.getElementById('content')); } else toast(res.error, 'error');
}

async function doDeleteFile(id) {
    var res = await api('files/delete', {id: id});
    if (res.success) { toast('Deleted'); renderFiles(document.getElementById('content')); } else toast(res.error, 'error');
}
