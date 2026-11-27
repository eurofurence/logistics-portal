import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/push-notifications.js', import.meta.url), 'utf8');

async function browser({ permission = 'default', subscribed = false, disabled = false, failSave = false,
    waitForWorker = false, waitForPermission = false, alpineLoaded = false, secure = true, dismissed = false } = {}) {
    const calls = [];
    const events = new Map();
    const timers = new Map();
    const modalEvents = [];
    const storage = new Map(disabled ? [['portal-push-disabled', 'true']] : []);
    if (dismissed) {
        storage.set('portal-push-prompt-dismissed', 'true');
    }
    let factory;
    let resolveWorker;
    let resolvePermission;
    const subscription = {
        endpoint: 'https://fcm.googleapis.com/fcm/send/browser',
        toJSON() { return { endpoint: this.endpoint, keys: { p256dh: 'public', auth: 'auth' } }; },
        async unsubscribe() { calls.push('unsubscribe'); subscribed = false; },
    };
    const registration = { pushManager: {
        async getSubscription() { return subscribed ? subscription : null; },
        async subscribe(options) {
            assert.equal(options.userVisibleOnly, true);
            calls.push('subscribe');
            subscribed = true;
            return subscription;
        },
    } };
    const Notification = {
        permission,
        async requestPermission() {
            calls.push('permission');
            const result = waitForPermission ? await new Promise((resolve) => { resolvePermission = resolve; }) : 'granted';
            return this.permission = result;
        },
    };
    const window = { isSecureContext: secure, PushManager: {}, Notification };
    const Alpine = { data(name, value) { assert.equal(name, 'portalPushNotifications'); factory = value; } };
    if (alpineLoaded) {
        window.Alpine = Alpine;
    }
    vm.runInNewContext(source, {
        console: { warn() {} }, atob, Uint8Array, window, Notification,
        setTimeout(callback, delay) { assert.equal(delay, 10000); timers.set(1, callback); return 1; },
        clearTimeout(id) { timers.delete(id); },
        localStorage: {
            getItem: (key) => storage.get(key),
            setItem: (key, value) => storage.set(key, value),
            removeItem: (key) => storage.delete(key),
        },
        document: {
            addEventListener(name, callback) { events.set(name, callback); },
            querySelector: () => ({ content: 'csrf-token' }),
        },
        navigator: { serviceWorker: { ready: waitForWorker
            ? new Promise((resolve) => { resolveWorker = resolve; }) : Promise.resolve(registration) } },
        async fetch(url, options) {
            calls.push({ method: options.method, csrf: options.headers['X-CSRF-TOKEN'], body: JSON.parse(options.body) });
            return { ok: !failSave };
        },
    });
    if (!alpineLoaded) {
        assert.equal(factory, undefined);
        window.Alpine = Alpine;
        events.get('alpine:init')();
    }
    const component = factory();
    component.$nextTick = (callback) => Promise.resolve().then(callback);
    component.$dispatch = (name, detail) => modalEvents.push({ name, id: detail.id });
    component.$el = { dataset: { publicKey: 'BAAA', storeUrl: '/subscriptions', deleteUrl: '/subscriptions',
        enableLabel: 'Enable', disableLabel: 'Disable', deniedLabel: 'Blocked', errorLabel: 'Failed',
        activeLabel: 'Enabled on this device', inactiveLabel: 'Disabled on this device', busyLabel: 'Please wait',
        retryLabel: 'Retry', workerErrorLabel: 'Worker unavailable' } };
    const initialized = component.init();
    await new Promise(setImmediate);
    return {
        calls, component, storage, timers, initialized, modalEvents,
        click: () => component.toggle(),
        expireWorker: () => timers.get(1)(),
        resolveWorker: () => resolveWorker(registration),
        resolvePermission: (result) => resolvePermission(result),
        allowSave() { failSave = false; },
    };
}

test('asks for permission only after a click and saves the browser subscription with CSRF protection', async () => {
    const app = await browser();
    assert.equal(app.component.supported, true);
    assert.equal(app.component.status, 'Disabled on this device');
    assert.deepEqual(app.calls, []);
    await app.click();
    assert.equal(app.calls[0], 'permission');
    assert.equal(app.calls[1], 'subscribe');
    assert.equal(app.calls[2].method, 'POST');
    assert.equal(app.calls[2].csrf, 'csrf-token');
    assert.equal(app.component.buttonLabel, 'Disable');
    assert.equal(app.component.status, 'Enabled on this device');
});

test('synchronizes an existing permitted subscription without asking for permission', async () => {
    const app = await browser({ permission: 'granted', subscribed: true, alpineLoaded: true });
    assert.equal(app.calls.length, 1);
    assert.equal(app.calls[0].method, 'POST');
    assert.equal(app.component.buttonLabel, 'Disable');
});

test('remembers disabling push so granted browser permission does not reenable it on reload', async () => {
    const app = await browser({ permission: 'granted', subscribed: true });
    await app.click();
    assert.equal(app.calls[1].method, 'DELETE');
    assert.equal(app.calls[2], 'unsubscribe');
    assert.equal(app.storage.get('portal-push-disabled'), 'true');

    const reloaded = await browser({ permission: 'granted', disabled: true });
    assert.deepEqual(reloaded.calls, []);
    assert.equal(reloaded.component.buttonLabel, 'Enable');
});

test('explains blocked permissions without prompting or subscribing', async () => {
    const app = await browser({ permission: 'denied' });
    assert.deepEqual(app.calls, []);
    assert.equal(app.component.actionDisabled, true);
    assert.equal(app.component.status, 'Blocked');
});

test('reports failed subscription persistence to the user and allows retrying', async () => {
    const app = await browser({ permission: 'granted', failSave: true });
    assert.equal(app.component.status, 'Failed');
    assert.equal(app.component.enabled, false);
    assert.equal(app.component.buttonLabel, 'Retry');
    app.allowSave();
    await app.click();
    assert.equal(app.component.status, 'Enabled on this device');
    assert.equal(app.calls.filter((call) => call === 'subscribe').length, 1);
});

test('disables activation until the worker is ready and offers retry after ten seconds', async () => {
    const app = await browser({ waitForWorker: true });
    assert.equal(app.component.actionDisabled, true);
    assert.equal(app.component.buttonLabel, 'Please wait');
    await app.click();
    assert.deepEqual(app.calls, []);
    app.expireWorker();
    await app.initialized;
    assert.equal(app.component.status, 'Worker unavailable');
    assert.equal(app.component.buttonLabel, 'Retry');
    assert.equal(app.component.actionDisabled, false);
    app.resolveWorker();
    await app.click();
    assert.equal(app.component.ready, true);
    assert.equal(app.component.buttonLabel, 'Enable');
    assert.equal(app.timers.size, 0);
});

test('ignores repeated activation clicks while permission is pending and explains a denial', async () => {
    const app = await browser({ waitForPermission: true });
    const activation = app.click();
    assert.equal(app.component.pending, true);
    assert.equal(app.component.buttonLabel, 'Please wait');
    assert.equal(app.calls[0], 'permission');
    await app.click();
    assert.equal(app.calls.length, 1);
    app.resolvePermission('denied');
    await activation;
    assert.equal(app.component.status, 'Blocked');
    assert.equal(app.component.actionDisabled, true);
});

test('reports an activation save error without falsely marking push as enabled', async () => {
    const app = await browser({ failSave: true });
    await app.click();
    assert.equal(app.component.enabled, false);
    assert.equal(app.component.pending, false);
    assert.equal(app.component.status, 'Failed');
    app.allowSave();
    await app.click();
    assert.equal(app.component.enabled, true);
});

test('hides push controls in an insecure context without initializing the worker', async () => {
    const app = await browser({ secure: false });
    assert.equal(app.component.supported, false);
    assert.deepEqual(app.calls, []);
    assert.equal(app.timers.size, 0);
    assert.deepEqual(app.modalEvents, []);
});

test('opens the popup automatically for an undecided browser without requesting permission', async () => {
    const app = await browser();
    assert.deepEqual(app.modalEvents, [{ name: 'open-modal', id: 'portal-push-settings' }]);
    assert.deepEqual(app.calls, []);
});

test('does not open the popup automatically after a browser permission decision', async () => {
    for (const permission of ['granted', 'denied']) {
        const app = await browser({ permission });
        assert.deepEqual(app.modalEvents, []);
        app.component.openSettings();
        assert.equal(app.modalEvents.at(-1).id, 'portal-push-settings');
    }
});

test('remembers closing the popup and still allows opening it manually from the profile', async () => {
    const app = await browser();
    app.component.dismissPrompt();
    assert.equal(app.storage.get('portal-push-prompt-dismissed'), 'true');

    const reloaded = await browser({ dismissed: true });
    assert.deepEqual(reloaded.modalEvents, []);
    assert.deepEqual(reloaded.calls, []);
    reloaded.component.openSettings();
    assert.deepEqual(reloaded.modalEvents, [{ name: 'open-modal', id: 'portal-push-settings' }]);
    assert.equal(reloaded.storage.get('portal-push-prompt-dismissed'), 'true');
});
