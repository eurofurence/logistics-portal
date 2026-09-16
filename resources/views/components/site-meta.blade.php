@php
    $siteSettings = app(\App\Settings\GeneralSettings::class);
    $themeSettings = app(\App\Settings\ThemeSettings::class);
    $siteName = $siteSettings->displayName();
    $indexingEnabled = ($allowIndexing ?? true) && ! auth()->check() && $siteSettings->search_engine_indexing;
@endphp

<meta name="robots" content="{{ $indexingEnabled ? 'index, follow' : 'noindex, nofollow' }}">
@if (filled($siteSettings->site_description))
    <meta name="description" content="{{ $siteSettings->site_description }}">
    <meta property="og:description" content="{{ $siteSettings->site_description }}">
    <meta name="twitter:description" content="{{ $siteSettings->site_description }}">
@endif
@if (filled($siteSettings->seo_keywords))
    <meta name="keywords" content="{{ $siteSettings->seo_keywords }}">
@endif
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $siteName }}">
<meta property="og:url" content="{{ request()->url() }}">
<meta property="og:image" content="{{ $themeSettings->socialImageUrl() }}">
<meta name="twitter:card" content="{{ $themeSettings->social_image ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $siteName }}">
<meta name="twitter:image" content="{{ $themeSettings->socialImageUrl() }}">
