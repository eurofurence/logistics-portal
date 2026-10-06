<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('login.passkeys_enabled')) {
            $this->migrator->add('login.passkeys_enabled', false);
        }
    }
};
