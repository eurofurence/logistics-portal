<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $timezone = 'Europe/Berlin';

    public string $default_locale = 'en';

    public string $dashboard_url = 'https://identity.eurofurence.org';

    public ?string $site_name = null;

    public ?string $site_description = null;

    public ?string $seo_keywords = null;

    public bool $search_engine_indexing = false;

    public bool $homepage_overview_enabled = true;

    public bool $homepage_guide_enabled = true;

    public bool $homepage_overview_orders = true;

    public bool $homepage_overview_bills = true;

    public bool $homepage_overview_wishlists = true;

    public bool $homepage_overview_inventory = true;

    public bool $homepage_guide_orders = true;

    public bool $homepage_guide_bills = true;

    public bool $homepage_guide_wishlists = true;

    public bool $homepage_guide_inventory = true;

    public ?string $homepage_title = null;

    public ?string $homepage_intro = null;

    public ?string $homepage_orders_description = null;

    public ?string $homepage_bills_description = null;

    public ?string $homepage_wishlists_description = null;

    public ?string $homepage_inventory_description = null;

    public function displayName(): string
    {
        return filled($this->site_name) ? $this->site_name : config('app.name');
    }

    public static function group(): string
    {
        return 'general';
    }
}
