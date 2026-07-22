/* PANDA v2 shell behaviors, ported from project-overview/panda-ui-concept.js.
   Everything binds at the document level (event delegation) so it keeps working
   after Livewire wire:navigate swaps the DOM. Screen-specific behaviors from the
   mockup (tag simulation, danger-zone previews, subtabs…) are NOT ported here —
   they become Livewire component logic in their own scaffold steps. */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/* ---------- real-time notification bell (Reverb) ----------
   In-app bell only — no OS-level push (that's the FCM half of the guide, skipped
   on purpose). Guarded by data-user-id on <body>, which only the app layout sets
   (login/print are standalone and never subscribe). On a live notification we
   don't manage any client-side notification list — NotificationBell is a normal
   Livewire component, so we just ask it to re-render and it re-fetches from the
   database itself; the broadcast payload only ever needs to say "something happened". */
const userId = document.body?.dataset?.userId;
if (userId) {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    window.Echo.private(`App.Models.User.${userId}`)
        .notification((notification) => {
            window.showToast?.(`${notification.title} — ${notification.body}`);
            window.Livewire?.dispatch('notification-received');
        });
}

/* ---------- theme toggle ---------- */
const root = document.documentElement;
const SUN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>';
const MOON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';

function effectiveTheme() {
    return root.dataset.theme
        || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
}

function paintThemeButton() {
    const btn = document.getElementById('theme-toggle');
    if (!btn) return;
    const dark = effectiveTheme() === 'dark';
    btn.innerHTML = dark ? SUN : MOON;
    btn.title = dark ? 'Switch to light theme' : 'Switch to dark theme';
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('#theme-toggle')) return;
    const next = effectiveTheme() === 'dark' ? 'light' : 'dark';
    root.dataset.theme = next;
    try { localStorage.setItem('panda-theme', next); } catch {}
    paintThemeButton();
});
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', paintThemeButton);
paintThemeButton();
document.addEventListener('livewire:navigated', paintThemeButton);

/* ---------- notification bell (unread state is Livewire's; only open/close lives here) ---------- */
document.addEventListener('click', (e) => {
    const panel = document.getElementById('notif-panel');
    if (!panel) return;
    if (e.target.closest('#notif-btn')) {
        panel.classList.toggle('open');
        return;
    }
    if (!e.target.closest('#notif-panel')) panel.classList.remove('open');
});

/* ---------- kebab (⋯) row menus: one open at a time ---------- */
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.kbtn');
    const open = document.querySelectorAll('.kebab.open');
    if (!btn) { open.forEach((k) => k.classList.remove('open')); return; }
    const kebab = btn.parentElement;
    const wasOpen = kebab.classList.contains('open');
    open.forEach((k) => k.classList.remove('open'));
    if (!wasOpen) kebab.classList.add('open');
});

/* ---------- modals (x-modal): open via [data-modal-open], close via [data-close] / backdrop ---------- */
document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-modal-open]');
    if (opener) {
        document.getElementById(opener.dataset.modalOpen)?.classList.add('on');
        return;
    }
    const closer = e.target.closest('[data-close]');
    if (closer) {
        closer.closest('.overlay')?.classList.remove('on');
        return;
    }
    if (e.target.classList?.contains('overlay')) e.target.classList.remove('on');
});

/* ---------- Escape closes any floating surface ---------- */
document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.getElementById('notif-panel')?.classList.remove('open');
    document.querySelectorAll('.kebab.open').forEach((k) => k.classList.remove('open'));
    document.querySelectorAll('.overlay.on').forEach((m) => m.classList.remove('on'));
});

/* ---------- Action Reference "To" inputs: typed values highlight green ---------- */
document.addEventListener('input', (e) => {
    if (e.target.classList?.contains('toin'))
        e.target.classList.toggle('filled', e.target.value.trim() !== '');
});

/* ---------- toast ---------- */
let toastTimer;
window.showToast = function (msg) {
    const t = document.getElementById('toast');
    const m = document.getElementById('toast-msg');
    if (!t || !m) return;
    m.textContent = msg;
    t.classList.add('on');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('on'), 3500);
};

/* ---------- styled wire:confirm (global interceptor) ----------
   Every wire:confirm in the app gets the styled dialog instead of the browser
   prompt, with zero per-button changes: the click is intercepted in the capture
   phase, our modal asks, and on Continue the click is replayed with the native
   confirm() temporarily answering yes. Markup lives in layouts/app (#confirm-modal). */
let confirmBypass = null;
document.addEventListener('click', (e) => {
    const el = e.target.closest('[wire\\:confirm]');
    if (!el || el === confirmBypass) { confirmBypass = null; return; }

    const modal = document.getElementById('confirm-modal');
    if (!modal) return; // standalone pages (login/print) fall back to the native prompt

    e.preventDefault();
    e.stopImmediatePropagation();
    e.stopPropagation();

    document.getElementById('confirm-msg').textContent = el.getAttribute('wire:confirm');
    modal.classList.add('on');

    document.getElementById('confirm-ok').onclick = () => {
        modal.classList.remove('on');
        confirmBypass = el;
        const native = window.confirm;
        window.confirm = () => true; // answer Livewire's own prompt for this replay only
        el.click();
        window.confirm = native;
    };
}, true);
