<?php

namespace App\Filament\Admin\Pages;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Support\Htmlable;
use ShuvroRoy\FilamentSpatieLaravelHealth\Pages\HealthCheckResults as BaseHealthCheckResults;


class HealthCheckResults extends BaseHealthCheckResults
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-heart';

    /*
    public function getHeading(): string | Htmlable
    {
        return __('general.health');
    }
    */

    public static function getNavigationGroup(): ?string
    {
        return __('general.settings');
    }

    public static function canAccess(): bool
    {
        $panel_name = trim(preg_replace('/^[^.]+\.(.*?\.).*$/', '$1', Route::currentRouteName()), '.');

        if (!Auth::Check()) {
            return false;
        }

        return Auth::user()->can('access-healthchecks') && ($panel_name == 'admin');
    }

    public static function getNavigationLabel(): string
    {
        return Lang::get('general.health');
    }
}
