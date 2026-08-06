/* PWA install flow — plain script (not bundled), included on both the authenticated app
 * shell and the standalone login page so install is offered before and after sign-in.
 * Service worker registration + custom install button/iOS banner live here together since
 * they share the same "is this already installed" checks. */
(function () {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/service-worker.js').catch((err) => {
                console.warn('PANDA service worker registration failed:', err);
            });
        });
    }

    // Standalone/PWA windows have no browser chrome (tab spinner, address-bar progress
    // bar) to signal an in-flight request — a slow login there looks identical to a dead
    // click, so users tend to hit Sign in repeatedly. Not gated on isStandalone(): the
    // same double-submit protection is harmless (and still useful) in a regular browser tab.
    const loginForm = document.querySelector('form.card');
    const signinBtn = document.getElementById('signin-btn');
    if (loginForm && signinBtn) {
        loginForm.addEventListener('submit', () => {
            signinBtn.disabled = true;
            document.getElementById('signin-label').innerHTML = '<span class="spinner" aria-hidden="true"></span>Signing in…';
        });
    }

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true; // legacy iOS Safari flag
    }

    function isIosSafari() {
        const ua = window.navigator.userAgent;
        const isIos = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
        const isSafari = /Safari/.test(ua) && !/CriOS|FxiOS|EdgiOS|OPiOS/.test(ua);
        return isIos && isSafari;
    }

    const installBtn = document.getElementById('pwa-install-btn');
    const iosBanner = document.getElementById('pwa-ios-banner');
    let deferredPrompt = null;

    if (isStandalone()) {
        // Already installed and running as an app — nothing to offer.
        return;
    }

    if (isIosSafari()) {
        iosBanner?.removeAttribute('hidden');
        document.getElementById('pwa-ios-banner-close')?.addEventListener('click', () => {
            iosBanner?.setAttribute('hidden', '');
        });
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        installBtn?.removeAttribute('hidden');
    });

    installBtn?.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        installBtn.setAttribute('hidden', '');
        deferredPrompt.prompt();
        await deferredPrompt.userChoice;
        deferredPrompt = null;
    });

    window.addEventListener('appinstalled', () => {
        installBtn?.setAttribute('hidden', '');
        iosBanner?.setAttribute('hidden', '');
        deferredPrompt = null;
    });
})();
