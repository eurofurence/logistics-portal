<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('theme.favicon', null);
        $this->migrator->add('theme.social_image', null);
        $this->migrator->add('general.site_name', null);
        $this->migrator->add('general.site_description', null);
        $this->migrator->add('general.seo_keywords', null);
        $this->migrator->add('general.search_engine_indexing', false);
    }

    public function down(): void
    {
        $this->migrator->delete('general.search_engine_indexing');
        $this->migrator->delete('general.seo_keywords');
        $this->migrator->delete('general.site_description');
        $this->migrator->delete('general.site_name');
        $this->migrator->delete('theme.social_image');
        $this->migrator->delete('theme.favicon');
    }
};
