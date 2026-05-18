<?php

namespace App\Filament\Pages\Auth;

use Filament\Schemas\Schema; // Zurück zum originalen Schema aus deinem Projekt
use Filament\Actions\Action;

class Login extends \Filament\Auth\Pages\Login
{
    // Nutzt wieder 'Schema', um mit der Basisklasse kompatibel zu sein
    public function form(Schema $schema): Schema
    {
        return parent::form($schema);
    }

    // In deiner Filament-Version überschreiben wir die Actions über diese Methode
    protected function getActions(): array
    {
        // Holt die Standard-Actions (falls vorhanden)
        // Falls 'parent::getActions()' auch einen Fehler wirft, kannst du es durch '$actions = [];' ersetzen
        $actions = method_exists(parent::class, 'getActions') ? parent::getActions() : [];

        if (config('app.identity_mode')) {
            $actions[] = Action::make('sso_login')
                ->url('/app/oauth/identity')
                ->label('EF Identity')
                ->icon('heroicon-o-cursor-arrow-rays')
                ->color('gray');
        }

        return $actions;
    }
}
