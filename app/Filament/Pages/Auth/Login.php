<?php

namespace App\Filament\Pages\Auth;

use Filament\Schemas\Schema; // Zurück zum originalen Schema aus deinem Projekt
use Filament\Actions\Action;

class Login extends \Filament\Auth\Pages\Login
{
   public function form(Schema $schema): Schema
    {
        return parent::form($schema);
    }

    protected function getFormActions(): array
    {
        $actions = parent::getFormActions();

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
