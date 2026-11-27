<?php

namespace App\Listeners;

use App\Filament\PanelSpotlightPlugin;
use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Filament\Support\Contracts\HasLabel;
use LivewireUI\Spotlight\Spotlight;
use pxlrbt\FilamentSpotlight\Actions\RegisterResources;
use pxlrbt\FilamentSpotlight\Actions\RegisterUserMenu;
use pxlrbt\FilamentSpotlight\Commands\PageCommand;

class RegisterPanelSpotlight
{
    public function handle(ServingFilament $event): void
    {
        $this->clear();

        $panel = Filament::getCurrentPanel();

        if ($panel === null || ! $panel->hasPlugin(PanelSpotlightPlugin::$name)) {
            return;
        }

        foreach (config('livewire-ui-spotlight.commands', []) as $command) {
            Spotlight::registerCommand($command);
        }

        if (! Filament::auth()->check()) {
            return;
        }

        config()->set('livewire-ui-spotlight.include_js', false);

        foreach ($panel->getPages() as $pageClass) {
            if (preg_match('/{[^}]+}/', $pageClass::getSlug($panel)) === 1
                || (method_exists($pageClass, 'shouldRegisterSpotlight') && ! $pageClass::shouldRegisterSpotlight())
                || ! $pageClass::canAccess()) {
                continue;
            }

            $page = new $pageClass;
            $group = $page->getNavigationGroup();
            $name = collect([
                $group instanceof HasLabel ? $group->getLabel() : $group,
                $page->getTitle(),
            ])->filter()->join(' / ');
            $url = $pageClass::getUrl(panel: $panel->getId());

            if (blank($name) || blank($url)) {
                continue;
            }

            $command = new PageCommand(name: $name, url: $url);
            Spotlight::$commands[$command->getId()] = $command;
        }

        RegisterResources::boot($panel);
        RegisterUserMenu::boot($panel);
    }

    public function clear(): void
    {
        Spotlight::$commands = [];
    }
}
