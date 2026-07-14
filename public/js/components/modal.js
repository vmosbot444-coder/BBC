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
function showModal(html, danger) {
    const m = document.getElementById('modalContent');
    m.innerHTML = html;
    m.className = 'modal' + (danger ? ' modal-danger' : '');
    document.getElementById('modalOverlay').classList.add('show');
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
}

function selectRadio(el, groupId) {
    document.getElementById(groupId).querySelectorAll('.radio-option').forEach(r => r.classList.remove('selected'));
    el.classList.add('selected');
}

function getRadioVal(groupId) {
    const sel = document.getElementById(groupId).querySelector('.selected');
    return sel ? sel.dataset.val : '';
}

function confirmAction(title, desc, btnText, onConfirm) {
    showModal(
        '<div class="modal-title">' + title + '</div>' +
        '<p style="color:var(--text-secondary);font-size:13px">' + desc + '</p>' +
        '<div class="modal-actions"><button class="btn btn-secondary" onclick="closeModal()">Cancel</button>' +
        '<button class="btn btn-danger" id="confirmBtn">' + btnText + '</button></div>',
        true
    );
    document.getElementById('confirmBtn').onclick = function() { closeModal(); onConfirm(); };
}
