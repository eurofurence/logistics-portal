<?php

namespace App\Filament\Admin\Resources;

use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Admin\Resources\UserResource\Pages\ListUsers;
use App\Filament\Admin\Resources\UserResource\Pages\CreateUser;
use App\Filament\Admin\Resources\UserResource\Pages\EditUser;
use App\Filament\Admin\Resources\UserResource\Pages\ViewUser;
use App\Models\Role;
use App\Models\User;
use Filament\Tables;
use App\Models\Department;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Admin\Resources\UserResource\Pages;

class UserResource extends Resource
{
    use Translatable;

    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.users');

        return static::$navigationGroup;
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['email', 'name', 'id'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'ID' => $record->id,
            'Email' => $record->email,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationLabel(): string
    {
        return __('general.users');
    }

    public static function getModelLabel(): string
    {
        return __('general.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.users');
    }

    public static function form(Schema $schema): Schema
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
                        ->label(__('general.notification_email')), //notification_email
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
                        ->options(Department::all()->pluck('name', 'id'))
                        ->relationship(name: 'departments', titleAttribute: 'name'),
                    Select::make('roles')
                        ->label(__('general.user_roles'))
                        ->multiple()
                        ->searchable()
                        ->nullable()
                        ->exists('roles', 'id')
                        ->options(Role::all()->pluck('name', 'id'))
                        ->preload(true)
                        ->relationship(name: 'roles', titleAttribute: 'name')
                        ->disabled(!Gate::check('update-Role'))
                        ->visible(Gate::check('update-Role')),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->searchable()
                    ->sortable()
                    ->label(__('general.id')),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('general.name')),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->label(__('general.email')),
                #TODO
                    /*
                IconColumn::make('locked')
                    ->label(__('general.locked'))
                    ->sortable()
    ->icon(fn (string $state): Heroicon => match ($state) {
        'draft' => Heroicon::OutlinedPencil,
        'reviewing' => Heroicon::OutlinedClock,
        'published' => Heroicon::OutlinedCheckCircle,
    })
        */
                TextColumn::make('email_verified_at')
                    ->dateTime(timezone: 'Europe/Berlin')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('general.email_verified_at')),
                TextColumn::make('created_at')
                    ->dateTime(timezone: 'Europe/Berlin')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('general.created_at')),
                TextColumn::make('updated_at')
                    ->dateTime(timezone: 'Europe/Berlin')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('general.updated_at')),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
                DeleteAction::make()
                    ->modalHeading(function ($record): string {
                        return __('general.delete') . ': ' . $record->name;
                    }),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(Gate::check('bulkDelete', User::class)),
                    RestoreBulkAction::make()
                        ->visible(Gate::check('bulkRestore', User::class)),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
            'view' => ViewUser::route('/{record}'),
        ];
    }

    public static function canAccess(): bool
    {
        if (!Auth::Check()) {
            return false;
        }

        return Auth::user()->checkPermissionTo('access-user-navigation') || Auth::user()->isSuperAdmin();
    }
}
