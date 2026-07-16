/* PANDA v2 shell behaviors, ported from project-overview/panda-ui-concept.js.
   Everything binds at the document level (event delegation) so it keeps working
   after Livewire wire:navigate swaps the DOM. Screen-specific behaviors from the
   mockup (tag simulation, danger-zone previews, subtabs…) are NOT ported here —
   they become Livewire component logic in their own scaffold steps. */

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

/* ---------- notification bell ---------- */
document.addEventListener('click', (e) => {
    const panel = document.getElementById('notif-panel');
    if (!panel) return;
    if (e.target.closest('#notif-btn')) {
        panel.classList.toggle('open');
        return;
    }
    if (e.target.closest('#notif-clear')) {
        panel.querySelectorAll('.nrow.unread').forEach((r) => r.classList.remove('unread'));
        const count = document.getElementById('notif-count');
        if (count) count.style.display = 'none';
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
