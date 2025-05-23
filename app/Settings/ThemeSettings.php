<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ThemeSettings extends Settings
{
    /** @var string  */
    public string $primary_color = 'rgb(1,80,75)';

    public static function group(): string
    {
        return 'theme';
    }
}
