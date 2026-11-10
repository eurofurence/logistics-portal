<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach (['overview', 'guide'] as $tab) {
            $this->migrator->add('general.homepage_'.$tab.'_enabled', true);
            foreach (['orders', 'bills', 'wishlists', 'inventory'] as $area) {
                $this->migrator->add('general.homepage_'.$tab.'_'.$area, true);
            }
        }
        foreach (['title', 'intro', 'orders_description', 'bills_description', 'wishlists_description', 'inventory_description'] as $field) {
            $this->migrator->add('general.homepage_'.$field, null);
        }
    }

    public function down(): void
    {
        foreach (['overview', 'guide'] as $tab) {
            $this->migrator->delete('general.homepage_'.$tab.'_enabled');
            foreach (['orders', 'bills', 'wishlists', 'inventory'] as $area) {
                $this->migrator->delete('general.homepage_'.$tab.'_'.$area);
            }
        }
        foreach (['title', 'intro', 'orders_description', 'bills_description', 'wishlists_description', 'inventory_description'] as $field) {
            $this->migrator->delete('general.homepage_'.$field);
        }
    }
};
