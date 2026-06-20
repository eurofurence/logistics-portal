<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use App\Models\PersonalAccessToken;

class EditProfile extends \Filament\Auth\Pages\EditProfile implements HasTable
{
    use InteractsWithTable;

    public ?string $newToken = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Notifications')
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
                        Tab::make('API')
                            ->schema([
                                Action::make('createToken')
                                    ->label(__('general.create_token'))
                                    ->icon('heroicon-o-plus')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('general.token_name'))
                                            ->required(),
                                        CheckboxList::make('abilities')
                                            ->label(__('general.token_abilities'))
                                            ->options([
                                                //Test Values
                                                'create' => 'Erstellen',
                                                'read' => 'Lesen',
                                                'update' => 'Aktualisieren',
                                                'delete' => 'Löschen',
                                            ]),
                                    ])
                                    ->action(function (array $data, $livewire) {
                                        $token = $livewire->getUser()->createToken($data['name'], $data['abilities']);
                                        Notification::make()
                                            ->title(__('general.token_created'))
                                            ->body(__('general.your_new_token') . ': ' . $token->plainTextToken)
                                            ->persistent()
                                            ->success()
                                            ->send();
                                    }),
                            ])
                            ->label(__('general.api'))
                            ->icon('heroicon-o-key')
                            ->visible(false)
                    ])
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getUser()->tokens()->getQuery())
            ->columns([
                TextColumn::make('name')
                    ->label(__('general.token_name')),
                TextColumn::make('created_at')
                    ->label(__('general.created_at'))
                    ->dateTime(),
            ])
            ->recordActions([

            ]);
    }
}
