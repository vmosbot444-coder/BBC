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
const API_BASE = window.location.origin + '/features/telegram';

function getParam(name) {
    const url = new URLSearchParams(window.location.search);
    return url.get(name) || '';
}

async function apiPost(endpoint, data = {}) {
    const res = await fetch(API_BASE + endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return res.json();
}

async function apiGet(endpoint) {
    const res = await fetch(API_BASE + endpoint);
    return res.json();
}

function formatPrice(paise) {
    return '₹' + (paise / 100).toLocaleString('en-IN');
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-IN', { month: 'short', day: 'numeric', year: 'numeric' });
}

function showAlert(container, type, message) {
    container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
}

function showLoading(container) {
    container.innerHTML = `<div class="loading"><div class="spinner"></div><p>Loading...</p></div>`;
}

function showEmpty(container, icon, message) {
    container.innerHTML = `<div class="empty"><div class="empty-icon">${icon}</div><p>${message}</p></div>`;
}

function closeTg() {
    if (window.Telegram && window.Telegram.WebApp) {
        window.Telegram.WebApp.close();
    }
}

function initTg() {
    if (window.Telegram && window.Telegram.WebApp) {
        const tg = window.Telegram.WebApp;
        tg.ready();
        tg.expand();
        document.body.style.background = tg.themeParams.bg_color || '#0E1117';
    }
}
