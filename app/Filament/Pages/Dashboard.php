<?php

namespace App\Filament\Pages;

use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static string $routePath = 'start';

    public static function getNavigationLabel(): string
    {
        return __('general.start');
    }

    public function getTitle(): string | Htmlable
    {
        return __('general.start');
    }
}
