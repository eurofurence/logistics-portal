<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class LoginSettings extends Settings
{
    /** @var bool  */
    public bool $whitelist_active = true;

    public static function group(): string
    {
        return 'login';
    }
}
