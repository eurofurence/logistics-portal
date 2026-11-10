import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const source = await readFile(new URL('../../public/sw.js', import.meta.url), 'utf8');
const registrationSource = await readFile(new URL('../../public/register-service-worker.js', import.meta.url), 'utf8');
const offlineHtml = await readFile(new URL('../../public/offline.html', import.meta.url), 'utf8');
const offlineSource = offlineHtml.match(/<script>([\s\S]*?)<\/script>/)[1];
const origin = 'https://logistics.example';

function worker() {
    const listeners = new Map();
    const stores = new Map();
    const network = [];
    const notifications = [];
    const windows = [];
    const opened = [];
    let fetchResponse = async () => ({ ok: true, redirected: false, type: 'basic', clone() { return this; } });
    const keyOf = (request) => typeof request === 'string' ? request : request.url;
    const caches = {
        async keys() { return [...stores.keys()]; },
        async delete(name) { return stores.delete(name); },
        async open(name) {
            if (!stores.has(name)) {
                stores.set(name, new Map());
            }
            const entries = stores.get(name);
            return {
                async add(request) { entries.set(keyOf(request), { offline: true }); },
                async match(request) { return entries.get(keyOf(request)); },
                async put(request, response) { entries.set(keyOf(request), response); },
                async keys() { return [...entries.keys()]; },
                async delete(request) { return entries.delete(keyOf(request)); },
            };
        },
    };
    vm.runInNewContext(source, {
        URL, Response, console, caches,
        Request: class { constructor(url) { this.url = url; } },
        fetch: async (request) => { network.push(request); return fetchResponse(request); },
        self: {
            location: { href: `${origin}/sw.js?v=release-2`, origin },
            addEventListener: (name, callback) => listeners.set(name, callback),
            skipWaiting: async () => {},
            registration: { showNotification: async (title, options) => notifications.push({ title, options }) },
            clients: {
                claim: async () => {},
                matchAll: async () => windows,
                openWindow: async (url) => opened.push(url),
            },
        },
    });

    return {
        stores, caches, network, notifications, windows, opened,
        async dispatch(name, properties) {
            let work;
            listeners.get(name)({ ...properties, waitUntil(promise) { work = promise; } });
            await work;
        },
        failNetwork() { fetchResponse = async () => { throw new TypeError('Offline'); }; },
        setResponse(response) { fetchResponse = async () => response; },
        async lifecycle(name) {
            let work;
            listeners.get(name)({ waitUntil(promise) { work = promise; } });
            await work;
        },
        async request(path, properties = {}) {
            let response;
            listeners.get('fetch')({
                request: { url: new URL(path, origin).href, method: 'GET', mode: 'cors', destination: '', ...properties },
                respondWith(promise) { response = promise; },
            });
            return response;
        },
    };
}

test('installs an offline page and removes only outdated application caches', async () => {
    const app = worker();
    await app.caches.open('ef-logistics-pwa-assets-release-1');
    await app.caches.open('another-application');
    await app.lifecycle('install');
    await app.lifecycle('activate');

    assert.deepEqual(await app.caches.keys(), ['another-application', 'ef-logistics-pwa-offline-release-2']);
    app.failNetwork();
    assert.deepEqual(await app.request('/app/start', { mode: 'navigate' }), { offline: true });
});

test('always loads authenticated pages from the network and never stores their contents', async () => {
    const app = worker();
    const response = { privatePage: true };
    app.setResponse(response);

    assert.equal(await app.request('/app/start', { mode: 'navigate' }), response);
    assert.equal(app.network.length, 1);
    assert.equal(app.stores.size, 0);
});

test('preserves server errors instead of replacing them with the offline page', async () => {
    const app = worker();
    const response = { status: 403 };
    app.setResponse(response);

    assert.equal(await app.request('/app/start', { mode: 'navigate' }), response);
});

test('does not intercept mutations, Livewire, protected media, admin pages or external requests', async () => {
    const app = worker();
    for (const [path, properties] of [
        ['/app/start', { method: 'POST', mode: 'navigate' }],
        ['/livewire/update', {}],
        ['/order-articles/3/images/265/thumbnail', { destination: 'image' }],
        ['/admin', { mode: 'navigate' }],
        ['/storage/private.pdf', {}],
        ['/js/filament/filament/app.js', { destination: 'script' }],
        ['/build/assets/app-abcdefgh.js?private=1', { destination: 'script' }],
        ['https://identity.example/login', { mode: 'navigate' }],
    ]) {
        assert.equal(await app.request(path, properties), undefined, path);
    }
    assert.equal(app.network.length, 0);
    assert.equal(app.stores.size, 0);
});

test('reuses versioned static assets offline and bounds the asset cache size', async () => {
    const app = worker();
    for (let index = 0; index < 101; index++) {
        await app.request(`/build/assets/theme-abcdefgh${index}.css`, { destination: 'style' });
    }
    const entries = app.stores.get('ef-logistics-pwa-assets-release-2');
    assert.equal(entries.size, 100);
    assert.equal(entries.has(`${origin}/build/assets/theme-abcdefgh0.css`), false);

    app.failNetwork();
    assert.equal((await app.request('/build/assets/theme-abcdefgh100.css', { destination: 'style' })).ok, true);
    assert.equal(app.network.length, 101);
});

test('does not cache failed or redirected static asset responses', async () => {
    for (const response of [
        { ok: false, redirected: false, type: 'basic' },
        { ok: true, redirected: true, type: 'basic' },
        { ok: true, redirected: false, type: 'opaque' },
    ]) {
        const app = worker();
        app.setResponse(response);
        assert.equal(await app.request('/build/assets/app-abcdefgh.js', { destination: 'script' }), response);
        assert.equal(app.stores.get('ef-logistics-pwa-assets-release-2').size, 0);
    }
});

test('registers the deployment version after load with app scope and fresh update checks', async () => {
    let onLoad;
    let registered;
    let stored;
    vm.runInNewContext(registrationSource, {
        navigator: { serviceWorker: {
            register: async (url, options) => { registered = { url, ...options }; },
        } },
        window: { isSecureContext: true, addEventListener(name, callback) {
            assert.equal(name, 'load');
            onLoad = callback;
        } },
        document: { currentScript: { dataset: { workerUrl: '/sw.js?v=release-2', offlineConfiguration: '{"name":"My Portal","locale":"de"}' } } },
        localStorage: { setItem(key, value) { stored = { key, value }; } },
        console,
    });
    assert.equal(registered, undefined);
    onLoad();
    assert.equal(registered.url, '/sw.js?v=release-2');
    assert.equal(registered.scope, '/app/');
    assert.equal(registered.updateViaCache, 'none');
    assert.equal(stored.key, 'portal-pwa');
    assert.equal(JSON.parse(stored.value).name, 'My Portal');
});

function offlinePage(configuration, languages = ['en'], storageUnavailable = false) {
    const elements = new Map(['portal-name', 'offline-title', 'offline-message', 'offline-retry'].map((id) => [id, {}]));
    const document = { documentElement: { lang: 'en' }, getElementById: (id) => elements.get(id) };
    vm.runInNewContext(offlineSource, {
        document,
        navigator: { languages },
        localStorage: { getItem() {
            if (storageUnavailable) {
                throw new Error('Storage disabled');
            }
            return configuration;
        } },
    });
    return { document, elements };
}

test('shows the saved portal name and selected application language while offline', () => {
    const { document, elements } = offlinePage(JSON.stringify({
        name: '<script>Custom Portal</script>',
        locale: 'de',
        translations: { de: { title: 'Keine Verbindung', message: 'Bitte verbinden.', retry: 'Neu laden' } },
    }), ['en']);

    assert.equal(document.documentElement.lang, 'de');
    assert.equal(document.title, 'Keine Verbindung · <script>Custom Portal</script>');
    assert.equal(elements.get('portal-name').textContent, '<script>Custom Portal</script>');
    assert.equal(elements.get('offline-message').textContent, 'Bitte verbinden.');
    assert.equal(elements.get('offline-retry').textContent, 'Neu laden');
});

test('uses Portal and the browser language when configuration is missing, invalid or unreadable', () => {
    for (const [configuration, unavailable] of [[null, false], ['invalid JSON', false], [null, true], ['{"name":"  "}', false]]) {
        const { document, elements } = offlinePage(configuration, ['de-DE', 'en'], unavailable);
        assert.equal(document.documentElement.lang, 'de');
        assert.equal(document.title, 'Du bist gerade offline · Portal');
        assert.equal(elements.get('portal-name').textContent, 'Portal');
    }
});

test('uses English when neither the selected nor browser languages are supported', () => {
    const { document } = offlinePage('{"locale":"fr"}', ['fr-FR']);
    assert.equal(document.documentElement.lang, 'en');
    assert.equal(document.title, 'You are offline · Portal');
});

test('does not attempt registration on insecure or unsupported browsers', () => {
    for (const [navigator, isSecureContext] of [[{}, true], [{ serviceWorker: {} }, false]]) {
        vm.runInNewContext(registrationSource, {
            navigator,
            window: { isSecureContext, addEventListener() { assert.fail('Unexpected registration'); } },
        });
    }
});

test('displays push messages with a stable tag and a portal destination', async () => {
    const app = worker();
    await app.dispatch('push', { data: { json: () => ({
        title: 'Order updated', body: 'Ready for collection', tag: 'database-123',
        data: { url: '/app/orders/123' },
    }) } });

    assert.equal(app.notifications.length, 1);
    assert.equal(app.notifications[0].title, 'Order updated');
    assert.equal(app.notifications[0].options.body, 'Ready for collection');
    assert.equal(app.notifications[0].options.tag, 'database-123');
    assert.equal(app.notifications[0].options.data.url, `${origin}/app/orders/123`);
});

test('ignores malformed push payloads and restricts click destinations to panel pages', async () => {
    const app = worker();
    await app.dispatch('push', { data: { json() { throw new Error('Invalid JSON'); } } });
    await app.dispatch('push', { data: { json: () => null } });
    assert.equal(app.notifications.length, 0);

    for (const url of ['https://untrusted.example/app', '/logout', 'javascript:alert(1)']) {
        let closed = false;
        await app.dispatch('notificationclick', { notification: {
            data: { url }, close() { closed = true; },
        } });
        assert.equal(closed, true);
        assert.equal(app.opened.at(-1), `${origin}/app`);
    }
});

test('focuses and navigates an existing portal window after a push click', async () => {
    const app = worker();
    let destination;
    let focused = false;
    app.windows.push({
        url: `${origin}/app`,
        async navigate(url) { destination = url; },
        async focus() { focused = true; },
    });
    await app.dispatch('notificationclick', { notification: {
        data: { url: '/app/orders/123' }, close() {},
    } });

    assert.equal(destination, `${origin}/app/orders/123`);
    assert.equal(focused, true);
    assert.equal(app.opened.length, 0);
});
