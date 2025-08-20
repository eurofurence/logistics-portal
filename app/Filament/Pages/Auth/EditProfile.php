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
                                    ->description(__('general.notification_email_description')),
                                Section::make(__('general.discord_webhook'))
                                    ->schema([
                                        TextInput::make('discord_webhook')
                                            ->label(__('general.webhook'))
                                            ->url()
                                            ->nullable()
                                            ->rules([
                                                'regex:/^https:\/\/discord\.com\/api\/webhooks\/\d+\/[a-zA-Z0-9\-_]+$/',
                                            ]),
                                    ])
                                    ->description(__('general.discord_webhook_description'))
                            ])
                            ->label(__('general.notifications'))
                            ->icon('heroicon-o-bell'),
                    ])
            ]);
    }
}
