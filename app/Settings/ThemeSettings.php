<?php

namespace App\Settings;

use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Settings;

class ThemeSettings extends Settings
{
    public string $primary_color = 'rgb(1,80,75)';

    public ?string $logo = null;

    public function logoUrl(): string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : asset('images/logo-round-filled.png');
    }

    public static function group(): string
    {
        return 'theme';
    }
}
