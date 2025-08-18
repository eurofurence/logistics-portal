<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class TypesAndUnits extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

    public static function getNavigationLabel(): string
    {
        return __('general.types_and_units');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('general.inventory') . ' (BETA)';
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('general.types_and_units');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (!Auth::Check()) {
            return false;
        }

        $panel_name = trim(preg_replace('/^[^.]+\.(.*?\.).*$/', '$1', Route::currentRouteName()), '.');

        if($panel_name != 'app') {
            return false;
        }

        foreach (static::getClusteredComponents() as $component) {
            if ($component::canAccess()) {
                return true;
            }
        }

        return false;
    }
}
