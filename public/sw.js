const VERSION = new URL(self.location.href).searchParams.get('v') || 'development';
const CACHE_PREFIX = 'ef-logistics-pwa-';
const OFFLINE_CACHE = `${CACHE_PREFIX}offline-${VERSION}`;
const ASSET_CACHE = `${CACHE_PREFIX}assets-${VERSION}`;
const OFFLINE_URL = '/offline.html';
const MAX_ASSETS = 100;

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(OFFLINE_CACHE).then(async (cache) => {
        await cache.add(new Request(OFFLINE_URL, { cache: 'reload' }));
        await self.skipWaiting();
    }));
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const names = await caches.keys();
        await Promise.all(names
            .filter((name) => name.startsWith(CACHE_PREFIX) && ![OFFLINE_CACHE, ASSET_CACHE].includes(name))
            .map((name) => caches.delete(name)));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        if (!url.pathname.startsWith('/app/')) {
            return;
        }

        event.respondWith(fetch(request).catch(async () => {
            const cache = await caches.open(OFFLINE_CACHE);
            return await cache.match(OFFLINE_URL) || Response.error();
        }));
        return;
    }

    const isVersionedAsset = url.pathname.startsWith('/build/assets/')
        && /-[\w-]{8,}\.(css|js|woff2?|ttf|otf)$/.test(url.pathname)
        && ['style', 'script', 'font'].includes(request.destination)
        && !url.search;

    if (!isVersionedAsset) {
        return;
    }

    event.respondWith((async () => {
        const cache = await caches.open(ASSET_CACHE);
        const cached = await cache.match(request);
        if (cached) {
            return cached;
        }

        const response = await fetch(request);
        if (response.ok && !response.redirected && response.type === 'basic') {
            try {
                await cache.put(request, response.clone());
                const keys = await cache.keys();
                await Promise.all(keys.slice(0, Math.max(0, keys.length - MAX_ASSETS)).map((key) => cache.delete(key)));
            } catch (error) {
                console.warn('Static asset could not be cached:', error);
            }
        }

        return response;
    })());
});
