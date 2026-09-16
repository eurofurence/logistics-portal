<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $timezone = 'Europe/Berlin';

    public static function group(): string
    {
        return 'general';
    }
}
