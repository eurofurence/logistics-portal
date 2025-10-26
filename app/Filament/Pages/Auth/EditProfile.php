<?php

namespace App\Filament\Pages\Auth;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;

class EditProfile extends \Filament\Auth\Pages\EditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Tab 1')
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
