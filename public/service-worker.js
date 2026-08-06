/* PANDA PWA service worker.
 * Most screens are Livewire-rendered per-user/per-request, so this deliberately does NOT
 * try to make the whole app work offline — that would mean caching stale, possibly
 * cross-user HTML. What it does cache:
 *   - /build/* — Vite's hashed, content-addressed output (safe to cache forever; a new
 *     build gets a new filename, so there's no staleness risk).
 *   - /images/* + manifest.json — static, rarely-changing assets.
 *   - the login page — used only as an offline fallback shell so a dropped connection
 *     shows something branded instead of the browser's default offline error.
 * Bump CACHE_NAME on any change here so old caches get swept on activate.
 */
const CACHE_NAME = 'panda-pwa-v2';
const OFFLINE_URL = '/login';
const PRECACHE_URLS = [OFFLINE_URL, '/manifest.json', '/images/icon-192.png', '/images/icon-512.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    const response = await fetch(request);
    if (response.ok) {
        const cache = await caches.open(CACHE_NAME);
        cache.put(request, response.clone());
    }
    return response;
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);
    const fetchPromise = fetch(request).then((response) => {
        if (response.ok) cache.put(request, response.clone());
        return response;
    }).catch(() => cached);

    return cached || fetchPromise;
}

async function networkFirstNavigate(request) {
    try {
        return await fetch(request);
    } catch {
        return (await caches.match(OFFLINE_URL)) || Response.error();
    }
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return; // never intercept Livewire actions/CSRF-bearing writes

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    if (url.pathname.startsWith('/build/')) {
        event.respondWith(cacheFirst(request));
        return;
    }

    if (url.pathname.startsWith('/images/') || url.pathname === '/manifest.json') {
        event.respondWith(staleWhileRevalidate(request));
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirstNavigate(request));
    }
});
