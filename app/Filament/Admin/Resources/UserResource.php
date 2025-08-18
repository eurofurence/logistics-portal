<?php

namespace App\Filament\Admin\Resources;

use App\Models\Role;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Department;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Concerns\Translatable;
use App\Filament\Admin\Resources\UserResource\Pages;
use Archilex\ToggleIconColumn\Columns\ToggleIconColumn;

class UserResource extends Resource
{
    use Translatable;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                        ->label(__('general.password')), //notification_email
                    TextInput::make('notification_email')
                        ->maxLength(255)
                        ->email()
                        ->label(__('general.notification_email')), //notification_email
                    TextInput::make('ex_id')
                        ->readOnly()
                        ->label(__('general.external_id')),
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
                ToggleIconColumn::make('locked')
                    ->sortable()
                    ->label(__('general.locked')),
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading(function ($record): string {
                        return __('general.delete') . ': ' . $record->name;
                    }),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(Gate::check('bulkDelete', User::class)),
                    Tables\Actions\RestoreBulkAction::make()
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
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
