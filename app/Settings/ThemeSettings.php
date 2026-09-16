<?php

namespace App\Settings;

use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Settings;

class ThemeSettings extends Settings
{
    public string $primary_color = 'rgb(1,80,75)';

    public ?string $logo = null;

    public ?string $favicon = null;

    public ?string $social_image = null;

    public function faviconUrl(): string
    {
        return $this->favicon ? Storage::disk('public')->url($this->favicon) : asset('favicon.ico');
    }

    public function socialImageUrl(): string
    {
        return $this->social_image ? Storage::disk('public')->url($this->social_image) : $this->logoUrl();
    }

    public function logoUrl(): string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : asset('images/logo-round-filled.png');
    }

    public static function group(): string
    {
        return 'theme';
    }
}
