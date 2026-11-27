(() => {
    function register() {
        window.Alpine.data('portalPushNotifications', () => {
            let configuration;
            let registration;
            let subscription = null;
            const preferenceKey = 'portal-push-disabled';
            const promptDismissedKey = 'portal-push-prompt-dismissed';
            let promptDismissed = false;

            async function save(method, url, data) {
                const response = await fetch(url, {
                    method,
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(data),
                });
                if (!response.ok) {
                    throw new Error('Push subscription could not be saved.');
                }
            }

            return {
                supported: false,
                ready: false,
                pending: false,
                enabled: false,
                blocked: false,
                initializationFailed: false,
                error: '',

                get buttonLabel() {
                    if (this.pending) {
                        return configuration.busyLabel;
                    }
                    if (this.initializationFailed) {
                        return configuration.retryLabel;
                    }
                    return this.enabled ? configuration.disableLabel : configuration.enableLabel;
                },

                get actionDisabled() {
                    return this.pending || this.blocked || (!this.ready && !this.initializationFailed);
                },

                get status() {
                    if (this.error) {
                        return this.error;
                    }
                    if (this.blocked) {
                        return configuration.deniedLabel;
                    }
                    if (this.pending) {
                        return configuration.busyLabel;
                    }
                    return this.enabled ? configuration.activeLabel : configuration.inactiveLabel;
                },

                init() {
                    configuration = this.$el.dataset;
                    this.supported = window.isSecureContext && 'serviceWorker' in navigator
                        && 'PushManager' in window && 'Notification' in window;
                    if (this.supported) {
                        try {
                            promptDismissed = localStorage.getItem(promptDismissedKey) === 'true';
                        } catch (error) {
                            console.warn('Push prompt preference could not be read:', error);
                        }
                        this.$nextTick(() => {
                            if (Notification.permission === 'default' && !promptDismissed) {
                                this.openSettings();
                            }
                        });
                        return this.initialize();
                    }
                },

                openSettings() {
                    if (this.supported) {
                        this.$dispatch('open-modal', { id: 'portal-push-settings' });
                    }
                },

                dismissPrompt() {
                    promptDismissed = true;
                    try {
                        localStorage.setItem(promptDismissedKey, 'true');
                    } catch (error) {
                        console.warn('Push prompt preference could not be saved:', error);
                    }
                },

                async initialize() {
                    if (this.pending) {
                        return;
                    }
                    this.pending = true;
                    this.initializationFailed = false;
                    this.error = '';
                    this.blocked = Notification.permission === 'denied';
                    let timeout;
                    try {
                        registration = await Promise.race([
                            navigator.serviceWorker.ready,
                            new Promise((resolve, reject) => {
                                timeout = setTimeout(() => reject(new Error('worker-timeout')), 10000);
                            }),
                        ]);
                        clearTimeout(timeout);
                        this.ready = true;
                        subscription = await registration.pushManager.getSubscription();
                        if (Notification.permission === 'granted' && localStorage.getItem(preferenceKey) !== 'true') {
                            await this.subscribe();
                        } else if (subscription) {
                            await save('DELETE', configuration.deleteUrl, { endpoint: subscription.endpoint });
                            await subscription.unsubscribe();
                            subscription = null;
                        }
                        this.enabled = subscription !== null;
                    } catch (error) {
                        this.initializationFailed = true;
                        this.error = error.message === 'worker-timeout' ? configuration.workerErrorLabel : configuration.errorLabel;
                        console.warn('Push initialization failed:', error);
                    } finally {
                        clearTimeout(timeout);
                        this.pending = false;
                    }
                },

                async subscribe() {
                    if (!subscription) {
                        const key = configuration.publicKey;
                        const bytes = atob((key + '='.repeat((4 - key.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/'));
                        subscription = await registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: Uint8Array.from(bytes, (character) => character.charCodeAt(0)),
                        });
                    }
                    await save('POST', configuration.storeUrl, subscription.toJSON());
                    this.enabled = true;
                },

                async toggle() {
                    if (this.actionDisabled) {
                        return;
                    }
                    if (this.initializationFailed) {
                        return this.initialize();
                    }
                    this.pending = true;
                    this.error = '';
                    try {
                        if (this.enabled) {
                            await save('DELETE', configuration.deleteUrl, { endpoint: subscription.endpoint });
                            await subscription.unsubscribe();
                            subscription = null;
                            this.enabled = false;
                            localStorage.setItem(preferenceKey, 'true');
                        } else {
                            const permission = await Notification.requestPermission();
                            this.blocked = permission === 'denied';
                            if (permission === 'granted') {
                                await this.subscribe();
                                localStorage.removeItem(preferenceKey);
                            }
                        }
                    } catch (error) {
                        this.error = configuration.errorLabel;
                        console.warn('Push setup failed:', error);
                    } finally {
                        this.pending = false;
                    }
                },
            };
        });
    }

    if (window.Alpine) {
        register();
    } else {
        document.addEventListener('alpine:init', register, { once: true });
    }
})();
