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
async function renderLogs(c, page) {
    if (role !== 'admin') { c.innerHTML = '<div class="empty-state">Admin only</div>'; return; }
    page = page || 1;
    var data = await api('logs?page=' + page);
    if (!data || !data.success) { c.innerHTML = '<div class="empty-state">Failed to load</div>'; return; }

    c.innerHTML = '<div class="card"><div class="card-header"><div class="card-title">Activity Log</div></div><div class="card-body">' +
        data.logs.map(function(l) {
            return '<div class="log-item"><span class="badge badge-' + (l.user_type==='admin'?'active':l.user_type==='seller'?'unused':'expired') + '">' + l.user_type + '</span><span class="log-action">' + l.action.replace(/_/g,' ') + '</span><span class="mono" style="color:var(--text-muted);font-size:10px">' + l.ip_address + '</span><span class="log-time">' + l.time_ago + '</span></div>';
        }).join('') +
        '</div>' + pagination(page, data.pages, 'loadLogsPage') + '</div>';
}

function loadLogsPage(p) { renderLogs(document.getElementById('content'), p); }
