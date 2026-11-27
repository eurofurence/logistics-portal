<?php

namespace App\Services;

use App\Settings\GeneralSettings;
use Throwable;

class PwaConfiguration
{
    public function name(): string
    {
        try {
            $name = app(GeneralSettings::class)->site_name;

            return filled($name) ? $name : 'Portal';
        } catch (Throwable) {
            return 'Portal';
        }
    }

    /** @return array<string, array<string, string>> */
    public function translations(): array
    {
        $translations = [];

        foreach (array_keys(config('app.available_locales')) as $locale) {
            foreach (['title', 'message', 'retry', 'description'] as $key) {
                $translations[$locale][$key] = trans('pwa.'.$key, [], $locale);
            }
        }

        return $translations;
    }
}
