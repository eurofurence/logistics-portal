<?php

namespace App\Filament\Admin\Pages;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use ShuvroRoy\FilamentSpatieLaravelHealth\Pages\HealthCheckResults as BaseHealthCheckResults;

class HealthCheckResults extends BaseHealthCheckResults
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';

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
        $user = Auth::user();
        $panel = Filament::getCurrentPanel();

        return $user !== null
            && $panel?->getId() === 'admin'
            && $user->canAccessPanel($panel)
            && $user->can('access-healthchecks');
    }

    public function refresh(): void
    {
        abort_unless(static::canAccess(), 403);

        parent::refresh();
    }

    public static function getNavigationLabel(): string
    {
        return Lang::get('general.health');
    }
}
