// A small service worker that makes GrainLedger Demo installable and lets the
// static app shell (CSS/JS/icons) load instantly on repeat visits.
//
// This app's data (items, stock, sales) is entered live through Livewire,
// which needs a network round-trip to save anything — so this deliberately
// does NOT try to cache or replay form submissions offline. It only speeds
// up static assets and shows a friendly page instead of the browser's
// default error when a navigation happens with no network at all.

const CACHE_VERSION = 'inventory-v1';
const OFFLINE_URL = '/offline.html';

const PRECACHE_URLS = [
    OFFLINE_URL,
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => cache.addAll(PRECACHE_URLS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Only ever handle simple, same-origin GETs. Livewire's requests (POST)
    // and anything cross-origin always go straight to the network.
    if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
        return;
    }

    // Full-page navigations: try the network first (so logged-in pages stay
    // fresh), and fall back to a cached offline page if there's no network.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    // Built static assets (hashed filenames from Vite) are safe to serve
    // cache-first, since a new build always ships a new filename.
    if (request.url.includes('/build/assets/') || request.url.includes('/icons/')) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                const copy = response.clone();
                caches.open(CACHE_VERSION).then((cache) => cache.put(request, copy));
                return response;
            }))
        );
    }
});
