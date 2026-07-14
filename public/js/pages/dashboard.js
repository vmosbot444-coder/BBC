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
async function renderDashboard(c) {
    var data = await api('dashboard');
    if (!data || !data.success) { c.innerHTML = '<div class="empty-state">Failed to load</div>'; return; }

    var html = '';
    if (role === 'admin') {
        var totalActive = data.active_keys || 0;
        var totalExpired = data.expired_keys || 0;
        var totalUnused = data.unused_keys || 0;
        var totalBanned = data.banned_keys || 0;
        var totalKeys = data.total_keys || 0;

        var activePercent = totalKeys ? Math.round((totalActive / totalKeys) * 100) : 0;
        var unusedPercent = totalKeys ? Math.round((totalUnused / totalKeys) * 100) : 0;
        var expiredPercent = totalKeys ? Math.round((totalExpired / totalKeys) * 100) : 0;

        html = '<div class="stats-grid stats-grid-5">' +
            '<div class="stat-card"><div class="stat-label">Total Keys</div><div class="stat-value" data-count="' + totalKeys + '">0</div><div class="stat-bar"><div class="stat-bar-fill" style="width:100%"></div></div></div>' +
            '<div class="stat-card"><div class="stat-label">Active</div><div class="stat-value stat-green" data-count="' + totalActive + '">0</div><div class="stat-bar"><div class="stat-bar-fill" style="width:' + activePercent + '%"></div></div><div class="stat-sub">' + activePercent + '% of total</div></div>' +
            '<div class="stat-card"><div class="stat-label">Unused</div><div class="stat-value stat-blue" data-count="' + totalUnused + '">0</div><div class="stat-bar"><div class="stat-bar-fill stat-bar-blue" style="width:' + unusedPercent + '%"></div></div><div class="stat-sub">' + unusedPercent + '% of total</div></div>' +
            '<div class="stat-card"><div class="stat-label">Expired</div><div class="stat-value stat-yellow" data-count="' + totalExpired + '">0</div><div class="stat-bar"><div class="stat-bar-fill stat-bar-yellow" style="width:' + expiredPercent + '%"></div></div></div>' +
            '<div class="stat-card"><div class="stat-label">Banned</div><div class="stat-value stat-red" data-count="' + totalBanned + '">0</div></div>' +
            '</div>';

        var rev = data.revenue || {};
        var defaultPeriod = rev['all'] || {collected:0, due:0};

        html += '<div class="grid-3">' +
            '<div class="stat-card" style="grid-column:span 2"><div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px"><div class="stat-label" style="margin:0">Revenue</div><div class="revenue-pills" id="revPills">' +
            '<span class="rev-pill" data-period="today" onclick="switchRevenue(this)">Today</span>' +
            '<span class="rev-pill" data-period="7d" onclick="switchRevenue(this)">7 Days</span>' +
            '<span class="rev-pill" data-period="30d" onclick="switchRevenue(this)">30 Days</span>' +
            '<span class="rev-pill active" data-period="all" onclick="switchRevenue(this)">ALL TIME</span>' +
            '</div></div><div style="display:flex;gap:16px"><div style="flex:1"><div style="font-size:10px;color:var(--text-muted);margin-bottom:4px">COLLECTED</div><div class="stat-value stat-green" id="revCollected" style="font-size:22px">₹' + Math.round(defaultPeriod.collected).toLocaleString('en-IN') + '</div></div><div style="flex:1"><div style="font-size:10px;color:var(--text-muted);margin-bottom:4px">DUE</div><div class="stat-value" id="revDue" style="font-size:22px;color:' + (defaultPeriod.due > 0 ? 'var(--danger)' : 'var(--text-muted)') + '">₹' + Math.round(defaultPeriod.due).toLocaleString('en-IN') + '</div></div></div></div>' +
            '<div class="stat-card"><div class="stat-label">Active Sellers</div><div class="stat-value" data-count="' + (data.active_sellers || 0) + '">0</div><div class="stat-sub">' + (data.total_sellers || 0) + ' total · ' + (data.total_devices || 0) + ' devices</div></div>' +
            '</div>';

        window._revenueData = rev;

        var days7 = [];
        for (var di = 6; di >= 0; di--) {
            var dt = new Date();
            dt.setDate(dt.getDate() - di);
            var key = dt.toISOString().split('T')[0];
            var found = 0;
            if (data.keys_per_day) {
                data.keys_per_day.forEach(function(d) { if (d.date === key) found = d.count; });
            }
            days7.push({ date: key, count: found });
        }
        var maxCount = 1;
        var totalWeek = 0;
        days7.forEach(function(d) {
            if (d.count > maxCount) maxCount = d.count;
            totalWeek += d.count;
        });
        var chartBars = days7.map(function(d) {
            var h = d.count === 0 ? 3 : Math.max(8, (d.count / maxCount) * 100);
            var dt = new Date(d.date);
            var dayName = dt.toLocaleDateString('en', { weekday: 'short' });
            var dateLbl = dt.toLocaleDateString('en', { month: 'short', day: 'numeric' });
            var isEmpty = d.count === 0 ? ' chart-bar-empty' : '';
            return '<div class="chart-col" title="' + dateLbl + ': ' + d.count + ' keys">' +
                '<div class="chart-bar' + isEmpty + '" style="height:' + h + '%">' +
                (d.count > 0 ? '<span class="chart-val">' + d.count + '</span>' : '') +
                '</div>' +
                '<div class="chart-label">' + dayName + '</div>' +
                '<div class="chart-date">' + dateLbl + '</div></div>';
        }).join('');
        var gridLines = '<div class="chart-grid"><div></div><div></div><div></div></div>';

        html += '<div class="grid-2">' +
            '<div class="card"><div class="card-header"><div class="card-title">Keys Generated (7 days)</div><span class="chart-total">' + totalWeek + ' total</span></div><div class="card-body"><div class="chart">' + gridLines + chartBars + '</div></div></div>' +
            '<div class="card"><div class="card-header"><div class="card-title">Key Distribution</div></div><div class="card-body"><div class="dist-grid">' +
            '<div class="dist-item"><div class="dist-dot" style="background:var(--accent)"></div><div class="dist-info"><span>Active</span><span class="mono">' + totalActive + '</span></div><div class="dist-bar"><div style="width:' + activePercent + '%;background:var(--accent)"></div></div></div>' +
            '<div class="dist-item"><div class="dist-dot" style="background:#60A5FA"></div><div class="dist-info"><span>Unused</span><span class="mono">' + totalUnused + '</span></div><div class="dist-bar"><div style="width:' + unusedPercent + '%;background:#60A5FA"></div></div></div>' +
            '<div class="dist-item"><div class="dist-dot" style="background:#FBBF24"></div><div class="dist-info"><span>Expired</span><span class="mono">' + totalExpired + '</span></div><div class="dist-bar"><div style="width:' + expiredPercent + '%;background:#FBBF24"></div></div></div>' +
            '<div class="dist-item"><div class="dist-dot" style="background:var(--danger)"></div><div class="dist-info"><span>Banned</span><span class="mono">' + totalBanned + '</span></div><div class="dist-bar"><div style="width:' + (totalKeys ? Math.round((totalBanned/totalKeys)*100) : 0) + '%;background:var(--danger)"></div></div></div>' +
            '</div></div></div></div>';

        html += '<div class="grid-2">' +
            '<div class="card"><div class="card-header"><div class="card-title">Recent Activity</div></div><div class="card-body scroll-list">' +
            (data.recent_logs && data.recent_logs.length ? data.recent_logs.map(function(l) {
                var icon = l.action.includes('login') ? '>' : l.action.includes('key') ? '#' : l.action.includes('seller') ? '@' : l.action.includes('file') ? '^' : '-';
                return '<div class="log-item"><span class="log-icon">' + icon + '</span><span class="log-action">' + l.action.replace(/_/g, ' ') + '</span><span class="log-time">' + l.time_ago + '</span></div>';
            }).join('') : '<div class="empty-state">No activity</div>') +
            '</div></div>' +
            '<div class="card"><div class="card-header"><div class="card-title">Top Sellers</div></div><div class="card-body scroll-list">' +
            (data.top_sellers && data.top_sellers.length ? data.top_sellers.map(function(s, i) {
                return '<div class="log-item"><span class="rank rank-' + (i+1) + '">' + (i+1) + '</span><span style="flex:1">' + s.username + '</span><span class="mono" style="color:var(--accent)">' + s.key_count + ' keys</span><span class="mono" style="color:var(--text-muted);margin-left:8px">' + s.tokens + ' tkn</span></div>';
            }).join('') : '<div class="empty-state">No sellers yet</div>') +
            '</div></div></div>';

        html += '<div class="card" style="margin-top:12px"><div class="card-header"><div class="card-title">Quick Actions</div></div><div class="card-body"><div class="quick-actions">' +
            '<button class="quick-btn" onclick="navigate(\'keys\');setTimeout(showGenerateModal,300)"><span class="quick-icon">+</span>Generate Keys</button>' +
            '<button class="quick-btn" onclick="navigate(\'sellers\')"><span class="quick-icon">@</span>Manage Sellers</button>' +
            '<button class="quick-btn" onclick="navigate(\'files\')"><span class="quick-icon">^</span>Upload Files</button>' +
            '<button class="quick-btn" onclick="navigate(\'settings\')"><span class="quick-icon">::</span>Settings</button>' +
            '</div></div></div>';

    } else {
        var settingsData = await api('settings');
        durationOptions = settingsData && settingsData.success ? settingsData.duration_options || [] : [];

        var totalKeys = data.total_keys || 0;
        var activeKeys = data.active_keys || 0;
        var unusedKeys = data.unused_keys || 0;
        var expiredKeys = data.expired_keys || 0;
        var tokenPct = data.total_earned ? Math.round((data.total_spent / data.total_earned) * 100) : 0;

        html = '<div class="stats-grid stats-grid-5">' +
            '<div class="stat-card stat-card-highlight"><div class="stat-label">Token Balance</div><div class="stat-value stat-green" data-count="' + (data.tokens || 0) + '">0</div><div class="stat-bar"><div class="stat-bar-fill" style="width:' + (100 - tokenPct) + '%"></div></div><div class="stat-sub">' + tokenPct + '% spent</div></div>' +
            '<div class="stat-card"><div class="stat-label">Total Keys</div><div class="stat-value" data-count="' + totalKeys + '">0</div></div>' +
            '<div class="stat-card"><div class="stat-label">Active</div><div class="stat-value stat-green" data-count="' + activeKeys + '">0</div><div class="stat-sub">' + unusedKeys + ' unused</div></div>' +
            '<div class="stat-card"><div class="stat-label">Tokens Spent</div><div class="stat-value stat-yellow" data-count="' + (data.total_spent || 0) + '">0</div></div>' +
            '<div class="stat-card"><div class="stat-label">Tokens Earned</div><div class="stat-value" data-count="' + (data.total_earned || 0) + '">0</div></div>' +
            '</div>';

        var days7s = [];
        for (var si = 6; si >= 0; si--) {
            var sdt = new Date();
            sdt.setDate(sdt.getDate() - si);
            var skey = sdt.toISOString().split('T')[0];
            var sfound = 0;
            if (data.keys_per_day) {
                data.keys_per_day.forEach(function(d) { if (d.date === skey) sfound = d.count; });
            }
            days7s.push({ date: skey, count: sfound });
        }
        var maxCount = 1;
        var totalWeekS = 0;
        days7s.forEach(function(d) {
            if (d.count > maxCount) maxCount = d.count;
            totalWeekS += d.count;
        });
        var chartBars = days7s.map(function(d) {
            var h = d.count === 0 ? 3 : Math.max(8, (d.count / maxCount) * 100);
            var sdt = new Date(d.date);
            var dayName = sdt.toLocaleDateString('en', { weekday: 'short' });
            var dateLbl = sdt.toLocaleDateString('en', { month: 'short', day: 'numeric' });
            var isEmpty = d.count === 0 ? ' chart-bar-empty' : '';
            return '<div class="chart-col" title="' + dateLbl + ': ' + d.count + ' keys">' +
                '<div class="chart-bar' + isEmpty + '" style="height:' + h + '%">' +
                (d.count > 0 ? '<span class="chart-val">' + d.count + '</span>' : '') +
                '</div>' +
                '<div class="chart-label">' + dayName + '</div>' +
                '<div class="chart-date">' + dateLbl + '</div></div>';
        }).join('');
        var gridLinesS = '<div class="chart-grid"><div></div><div></div><div></div></div>';

        var activeP = totalKeys ? Math.round((activeKeys / totalKeys) * 100) : 0;
        var unusedP = totalKeys ? Math.round((unusedKeys / totalKeys) * 100) : 0;
        var expiredP = totalKeys ? Math.round((expiredKeys / totalKeys) * 100) : 0;

        html += '<div class="grid-2">' +
            '<div class="card"><div class="card-header"><div class="card-title">Your Keys (7 days)</div><span class="chart-total">' + totalWeekS + ' total</span></div><div class="card-body"><div class="chart">' + gridLinesS + chartBars + '</div></div></div>' +
            '<div class="card"><div class="card-header"><div class="card-title">Key Distribution</div></div><div class="card-body"><div class="dist-grid">' +
            '<div class="dist-item"><div class="dist-dot" style="background:var(--accent)"></div><div class="dist-info"><span>Active</span><span class="mono">' + activeKeys + '</span></div><div class="dist-bar"><div style="width:' + activeP + '%;background:var(--accent)"></div></div></div>' +
            '<div class="dist-item"><div class="dist-dot" style="background:#60A5FA"></div><div class="dist-info"><span>Unused</span><span class="mono">' + unusedKeys + '</span></div><div class="dist-bar"><div style="width:' + unusedP + '%;background:#60A5FA"></div></div></div>' +
            '<div class="dist-item"><div class="dist-dot" style="background:#FBBF24"></div><div class="dist-info"><span>Expired</span><span class="mono">' + expiredKeys + '</span></div><div class="dist-bar"><div style="width:' + expiredP + '%;background:#FBBF24"></div></div></div>' +
            '</div></div></div></div>';

        html += '<div class="grid-2">' +
            '<div class="card"><div class="card-header"><div class="card-title">Recent Keys</div></div><div class="card-body scroll-list">' +
            (data.recent_keys && data.recent_keys.length ? data.recent_keys.map(function(k) {
                return '<div class="log-item"><span class="mono" style="color:var(--text-secondary)">' + k.license_key.substring(0, 16) + '...</span>' + badgeHTML(k.status) + '<span class="log-time">' + k.time_ago + '</span></div>';
            }).join('') : '<div class="empty-state">No keys yet</div>') +
            '</div></div>' +
            '<div class="card"><div class="card-header"><div class="card-title">Recent Transactions</div></div><div class="card-body scroll-list">' +
            (data.recent_transactions && data.recent_transactions.length ? data.recent_transactions.map(function(tx) {
                var isSpend = tx.type === 'spend';
                return '<div class="log-item"><span class="log-icon">' + (isSpend ? '-' : '+') + '</span><span class="log-action">' + tx.type + '</span><span class="mono" style="color:' + (isSpend ? 'var(--danger)' : 'var(--accent)') + '">' + (isSpend ? '-' : '+') + tx.tokens + ' tkn</span><span class="log-time">' + tx.time_ago + '</span></div>';
            }).join('') : '<div class="empty-state">No transactions</div>') +
            '</div></div></div>';

        html += '<div class="card" style="margin-top:12px"><div class="card-header"><div class="card-title">Token Pricing</div></div><div class="card-body"><div class="pricing-grid">' +
            durationOptions.map(function(o) {
                return '<div class="pricing-item"><div class="pricing-days">' + o.label + '</div><div class="pricing-cost">' + o.token_cost + ' <span>token' + (o.token_cost > 1 ? 's' : '') + '</span></div></div>';
            }).join('') +
            '</div></div></div>';

        html += '<div class="card" style="margin-top:12px"><div class="card-header"><div class="card-title">Quick Actions</div></div><div class="card-body"><div class="quick-actions">' +
            '<button class="quick-btn" onclick="navigate(\'keys\');setTimeout(showGenerateModal,300)"><span class="quick-icon">+</span>Generate Keys</button>' +
            '<button class="quick-btn" onclick="navigate(\'keys\')"><span class="quick-icon">#</span>View My Keys</button>' +
            '</div></div></div>';
    }

    c.innerHTML = html;
    c.querySelectorAll('[data-count]').forEach(function(el) { animateCount(el, parseInt(el.dataset.count)); });
}

function switchRevenue(el) {
    document.querySelectorAll('.rev-pill').forEach(function(p) { p.classList.remove('active'); });
    el.classList.add('active');
    var period = el.dataset.period;
    var rev = window._revenueData || {};
    var data = rev[period] || {collected:0, due:0};
    var colEl = document.getElementById('revCollected');
    var dueEl = document.getElementById('revDue');
    if (colEl) colEl.textContent = '₹' + Math.round(data.collected).toLocaleString('en-IN');
    if (dueEl) {
        dueEl.textContent = '₹' + Math.round(data.due).toLocaleString('en-IN');
        dueEl.style.color = data.due > 0 ? 'var(--danger)' : 'var(--text-muted)';
    }
}
