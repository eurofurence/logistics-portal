<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\Department;
use App\Models\Role;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->label(__('general.name')),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->label(__('general.email')),
                    TextInput::make('password')
                        ->password()
                        ->maxLength(255)
                        ->label(__('general.password')),
                    TextInput::make('notification_email')
                        ->maxLength(255)
                        ->email()
                        ->label(__('general.notification_email')), // notification_email
                    TextInput::make('ex_id')
                        ->readOnly()
                        ->label(__('general.external_id')),
                    TextInput::make('discord_webhook')
                        ->label(__('general.discord_webhook'))
                        ->url()
                        ->nullable()
                        ->rules([
                            'regex:/^https:\/\/discord\.com\/api\/webhooks\/\d+\/[a-zA-Z0-9\-_]+$/',
                        ]),
                    TextInput::make('ex_groups')
                        ->readOnly()
                        ->label(__('general.idp_groups'))
                        ->visible(config('app.identity_mode')),
                    TextInput::make('avatar')
                        ->readOnly()
                        ->label(__('general.profile_picture')),
                    DateTimePicker::make('last_login')
                        ->readOnly()
                        ->label(__('general.last_login')),
                    Textarea::make('comment')
                        ->label(__('general.comment')),
                    Checkbox::make('locked')
                        ->label(__('general.locked')),
                    Checkbox::make('separated_rights')
                        ->label(__('general.separated_rights')),
                    Checkbox::make('separated_departments')
                        ->label(__('general.separated_departments')),
                    TextInput::make('created_at')
                        ->label(__('general.created_at'))
                        ->readOnly(),
                    TextInput::make('updated_at')
                        ->label(__('general.updated_at'))
                        ->readOnly(),
                ]),
                Section::make([
                    Select::make('departments')
                        ->label(__('general.departments'))
                        ->multiple()
                        ->searchable()
                        ->nullable()
                        ->preload(true)
                        ->exists('departments', 'id')
                        ->options(Department::query()->pluck('name', 'id'))
                        ->relationship(name: 'departments', titleAttribute: 'name'),
                    Select::make('roles')
                        ->label(__('general.user_roles'))
                        ->multiple()
                        ->searchable()
                        ->nullable()
                        ->exists('roles', 'id')
                        ->options(Role::query()->pluck('name', 'id'))
                        ->preload(true)
                        ->relationship(name: 'roles', titleAttribute: 'name')
                        ->disabled(! Gate::check('update-Role'))
                        ->visible(Gate::check('update-Role')),
                ]),
            ]);
    }
}
