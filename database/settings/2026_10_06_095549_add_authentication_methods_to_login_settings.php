<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('login.two_factor_enabled')) {
            $this->migrator->add('login.two_factor_enabled', false);
        }
    }
};
