<?php

namespace App\Filament;

use Filament\Panel;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;

class PanelSpotlightPlugin extends SpotlightPlugin
{
    /**
     * Navigation is registered by the ServingFilament listener for each request.
     */
    public function boot(Panel $panel): void {}
}
