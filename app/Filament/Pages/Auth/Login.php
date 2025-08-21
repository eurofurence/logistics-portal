<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([

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
