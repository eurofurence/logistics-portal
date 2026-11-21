<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <script>
            (() => {
                let theme = 'system'
                try {
                    theme = localStorage.getItem('member-portal-theme') || 'system'
                } catch {}
                const dark = theme === 'dark' || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)
                document.documentElement.classList.toggle('dark', dark)
                document.documentElement.style.colorScheme = dark ? 'dark' : 'light'
            })()
        </script>
        <title inertia>{{ __('member-portal.ui.portal') }}</title>
        <link rel="icon" href="{{ app(\App\Settings\ThemeSettings::class)->faviconUrl() }}">
        @vite('resources/js/member-portal.js')
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
