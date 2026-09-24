<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('theme.email_logo', null);
    }

    public function down(): void
    {
        $this->migrator->delete('theme.email_logo');
    }
};
