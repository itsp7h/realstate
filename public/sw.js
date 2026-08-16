// Minimal service worker: caches the static app shell (icons, manifest, offline
// fallback) so the icon/install experience is instant, but deliberately does
// NOT cache HTML pages or API responses — this app's data changes constantly,
// and a stale-cache dashboard/tenant-list would be worse than no offline
// support at all. Navigation only falls back to a static offline page when
// the network is genuinely unreachable.
const CACHE_NAME = 'p7h-shell-v1';
const SHELL_ASSETS = [
    '/offline.html',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/apple-touch-icon.png',
    '/manifest.json',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Only handle top-level page navigations specially (offline fallback).
    // Everything else (API calls, assets) goes straight to the network,
    // untouched, so data is never served stale.
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() => caches.match('/offline.html'))
        );
        return;
    }
});
