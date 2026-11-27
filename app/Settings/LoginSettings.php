<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class LoginSettings extends Settings
{
    public bool $whitelist_active = true;

    public bool $two_factor_enabled = false;

    public bool $passkeys_enabled = false;

    public static function group(): string
    {
        return 'login';
    }
}
