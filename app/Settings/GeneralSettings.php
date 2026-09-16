<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $timezone = 'Europe/Berlin';

    public ?string $site_name = null;

    public ?string $site_description = null;

    public ?string $seo_keywords = null;

    public bool $search_engine_indexing = false;

    public function displayName(): string
    {
        return filled($this->site_name) ? $this->site_name : config('app.name');
    }

    public static function group(): string
    {
        return 'general';
    }
}
