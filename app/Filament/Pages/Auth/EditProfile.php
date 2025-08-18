<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Form;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Tab 1')
                            ->schema([
                                Section::make(__('general.notification_email'))
                                    ->schema([
                                        TextInput::make('notification_email')
                                            ->label(__('general.email'))
                                            ->nullable()
                                            ->maxLength(255)
                                            ->email(),
                                    ])
                                    ->description(__('general.notification_email_description'))
                            ])
                            ->label(__('general.notifications'))
                            ->icon('heroicon-o-bell'),
                    ])
            ]);
    }
}
