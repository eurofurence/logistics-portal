@props([])

@php
    $logoUrl = null;
    $brandName = 'Portal';

    try {
        $brandName = app(\App\Settings\GeneralSettings::class)->displayName();
        $logoPath = app(\App\Settings\ThemeSettings::class)->logo;
        $logoUrl = filled($logoPath) ? route('branding.logo', ['v' => hash('sha256', $logoPath)], false) : null;
    } catch (\Throwable) {
        $logoUrl = null;
    }
@endphp

<span
    {{ $attributes->class(['fi-logo']) }}
    style="display: flex; align-items: center; justify-content: center; height: 3rem; min-width: 3rem"
    role="img"
    aria-label="{{ $brandName }}"
    x-data="{ logoAvailable: false }"
>
    @if ($logoUrl)
        <img
            src="{{ $logoUrl }}"
            alt=""
            aria-hidden="true"
            style="display: none; height: 3rem; width: auto; object-fit: contain"
            x-show="logoAvailable"
            x-init="if ($el.complete) { logoAvailable = $el.naturalWidth > 0 }"
            x-on:load="logoAvailable = true"
            x-on:error="logoAvailable = false"
        >
    @endif
    <x-heroicon-o-cube
        x-show="!logoAvailable"
        aria-hidden="true"
        style="height: 3rem; width: 3rem"
    />
</span>
