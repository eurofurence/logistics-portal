<?php

namespace App\Filament\Pages\Auth;

use App\Settings\LoginSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class EditProfile extends \Filament\Auth\Pages\EditProfile implements HasTable
{
    use InteractsWithTable;

    public ?string $newToken = null;

    public function getMultiFactorAuthenticationContentComponent(): ?Component
    {
        if (! app(LoginSettings::class)->two_factor_enabled) {
            return null;
        }

        $section = parent::getMultiFactorAuthenticationContentComponent();

        if ($section instanceof Section) {
            $section->heading(__('settings.two_factor_title'))
                ->label(null)
                ->description(__('settings.two_factor_description'))
                ->icon('heroicon-o-shield-check')
                ->compact(false)
                ->secondary(false);
        }

        return $section;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('profile-tabs')
                ->tabs([
                    Tab::make('notifications')
                        ->label(__('general.notifications'))
                        ->icon('heroicon-o-bell')
                        ->schema([$this->getFormContentComponent()]),
                    Tab::make('security')
                        ->label('Security')
                        ->icon('heroicon-o-shield-check')
                        ->schema(fn (): array => array_filter([
                            $this->getMultiFactorAuthenticationContentComponent(),
                            $this->getPasskeyContentComponent(),
                        ]))
                        ->visible(fn (): bool => app(LoginSettings::class)->two_factor_enabled || app(LoginSettings::class)->passkeys_enabled),
                ]),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->description(__('general.discord_webhook_description')),
                Section::make(__('general.api'))
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
                                        // Test Values
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
                                    ->body(__('general.your_new_token').': '.$token->plainTextToken)
                                    ->persistent()
                                    ->success()
                                    ->send();
                            }),
                    ])
                    ->icon('heroicon-o-key')
                    ->visible(false),
            ]);
    }

    public function getPasskeyContentComponent(): ?Component
    {
        if (! app(LoginSettings::class)->passkeys_enabled) {
            return null;
        }

        return Section::make(__('filament-passkeys::passkeys.passkeys'))
            ->description(__('filament-passkeys::passkeys.description'))
            ->icon('heroicon-o-key')
            ->schema([Livewire::make(ManagePasskeys::class)->key('profile-passkeys')]);
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
