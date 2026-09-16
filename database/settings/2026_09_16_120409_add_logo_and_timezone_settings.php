<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('theme.logo', null);
        $this->migrator->add('general.timezone', 'Europe/Berlin');
    }

    public function down(): void
    {
        $this->migrator->delete('general.timezone');
        $this->migrator->delete('theme.logo');
    }
};
