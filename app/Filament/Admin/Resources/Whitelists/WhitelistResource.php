<?php

namespace App\Filament\Admin\Resources\Whitelists;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Admin\Resources\Whitelists\Pages\ListWhitelists;
use App\Filament\Admin\Resources\Whitelists\Pages\CreateWhitelist;
use App\Filament\Admin\Resources\Whitelists\Pages\EditWhitelist;
use Filament\Tables;
use App\Models\Whitelist;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use App\Filament\Admin\Resources\WhitelistResource\Pages;

class WhitelistResource extends Resource
{
    protected static ?string $model = Whitelist::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-check';

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getPluralModelLabel(): string
    {
        return static::$pluralModelLabel = __('general.whitelist');
    }

    public static function getModelLabel(): string
    {
        return static::$modelLabel = __('general.whitelist');
    }


    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.users');

        return static::$navigationGroup;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('email')->required()->unique()->email(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('user.name')->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading(function ($record): string {
                        return __('general.delete') . ': ' . $record->email;
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(Gate::check('bulkDelete', Whitelist::class)),
                    RestoreBulkAction::make()
                        ->visible(Gate::check('bulkRestore', Whitelist::class)),
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
            'index' => ListWhitelists::route('/'),
            'create' => CreateWhitelist::route('/create'),
            'edit' => EditWhitelist::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        if (!Auth::Check()) {
            return false;
        }

        return (Auth::user()->checkPermissionTo('access-whitelist-navigation') || Auth::user()->isSuperAdmin());
    }
}
