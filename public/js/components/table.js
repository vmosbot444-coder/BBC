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
function buildTable(headers, rows, emptyMsg) {
    if (!rows.length) {
        return '<div class="empty-state"><div class="empty-art">/</div>' + (emptyMsg || 'No data') + '</div>';
    }
    let html = '<div class="table-wrap"><table><thead><tr>';
    headers.forEach(h => { html += '<th>' + h + '</th>'; });
    html += '</tr></thead><tbody>';
    rows.forEach(r => { html += '<tr>' + r + '</tr>'; });
    html += '</tbody></table></div>';
    return html;
}

function pagination(currentPage, totalPages, onClickFn) {
    if (totalPages <= 1) return '';
    let html = '<div class="pagination">';
    for (let i = 1; i <= Math.min(totalPages, 10); i++) {
        html += '<button class="page-btn ' + (i === currentPage ? 'active' : '') + '" onclick="' + onClickFn + '(' + i + ')">' + i + '</button>';
    }
    html += '</div>';
    return html;
}
