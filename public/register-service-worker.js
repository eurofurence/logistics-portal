(() => {
    try {
        const configuration = document.currentScript.dataset.offlineConfiguration;
        if (configuration) {
            localStorage.setItem('portal-pwa', configuration);
        }
    } catch (error) {
        console.warn('Offline configuration could not be saved:', error);
    }

    if (!('serviceWorker' in navigator) || !window.isSecureContext) {
        return;
    }

    const workerUrl = document.currentScript.dataset.workerUrl;

    window.addEventListener('load', () => {
        navigator.serviceWorker.register(workerUrl, {
            scope: '/app/',
            updateViaCache: 'none',
        }).catch((error) => {
            console.warn('Service worker registration failed:', error);
        });
    }, { once: true });
})();
