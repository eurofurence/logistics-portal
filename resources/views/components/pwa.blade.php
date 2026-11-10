@if (\Filament\Facades\Filament::getCurrentPanel()?->getId() === 'app')
    @php
        $buildManifest = public_path('build/manifest.json');
        $pwaConfiguration = app(\App\Services\PwaConfiguration::class);
        $offlineConfiguration = ['name' => $pwaConfiguration->name(), 'locale' => app()->getLocale(), 'translations' => $pwaConfiguration->translations()];
        $workerVersion = hash('sha256', hash_file('sha256', public_path('sw.js')).hash_file('sha256', public_path('offline.html')).(is_file($buildManifest) ? hash_file('sha256', $buildManifest) : 'development'));
    @endphp

    <link rel="manifest" href="{{ route('pwa.manifest', ['lang' => app()->getLocale()], false) }}">
    <meta name="theme-color" content="#007bff">
    <link rel="apple-touch-icon" href="/images/pwa-192.png">
    <script
        src="/register-service-worker.js?v={{ hash_file('sha256', public_path('register-service-worker.js')) }}"
        data-worker-url="/sw.js?v={{ $workerVersion }}"
        data-offline-configuration="{{ json_encode($offlineConfiguration, JSON_THROW_ON_ERROR) }}"
        defer
    ></script>
@endif
