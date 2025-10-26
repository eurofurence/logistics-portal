<?php

namespace App\Filament\Pages\Auth;

use Filament\Schemas\Schema;
use Filament\Actions\Action;

class Login extends \Filament\Auth\Pages\Login
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

            ]);

    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->url('/app/oauth/identity')
            ->label('EF Identity')
            ->icon('heroicon-o-cursor-arrow-rays');
    }
}
