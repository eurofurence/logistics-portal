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
                                    ->description(__('general.notification_email_description'))
                            ])
                            ->label(__('general.notifications'))
                            ->icon('heroicon-o-bell'),
                    ])
            ]);
    }
}
