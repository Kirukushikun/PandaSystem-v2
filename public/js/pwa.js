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
