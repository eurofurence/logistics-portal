<?php

namespace App\Filament\Admin\Resources\Whitelists;

use App\Filament\Admin\Resources\Whitelists\Pages\CreateWhitelist;
use App\Filament\Admin\Resources\Whitelists\Pages\EditWhitelist;
use App\Filament\Admin\Resources\Whitelists\Pages\ListWhitelists;
use App\Filament\Admin\Resources\Whitelists\Schemas\WhitelistForm;
use App\Filament\Admin\Resources\Whitelists\Tables\WhitelistsTable;
use App\Models\Whitelist;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WhitelistResource extends Resource
{
    protected static ?string $model = Whitelist::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCheck;

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
        return WhitelistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhitelistsTable::configure($table);
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
        if (! Auth::Check()) {
            return false;
        }

        return Auth::user()->checkPermissionTo('access-whitelist-navigation') || Auth::user()->isSuperAdmin();
    }
}
