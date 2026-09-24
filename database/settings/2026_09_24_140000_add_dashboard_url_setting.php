<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.dashboard_url', 'https://identity.eurofurence.org');
    }

    public function down(): void
    {
        $this->migrator->delete('general.dashboard_url');
    }
};
