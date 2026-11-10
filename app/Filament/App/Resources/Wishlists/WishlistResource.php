<?php

namespace App\Filament\App\Resources\Wishlists;

use App\Filament\App\Resources\Wishlists\Pages\CreateWishlist;
use App\Filament\App\Resources\Wishlists\Pages\EditWishlist;
use App\Filament\App\Resources\Wishlists\Pages\ListWishlists;
use App\Filament\App\Resources\Wishlists\RelationManagers\ItemsRelationManager;
use App\Filament\App\Resources\Wishlists\Schemas\WishlistForm;
use App\Filament\App\Resources\Wishlists\Tables\WishlistsTable;
use App\Models\Wishlist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WishlistResource extends Resource
{
    protected static ?string $model = Wishlist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('wishlists.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('wishlists.title');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->accessibleTo(auth()->user());
    }

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.orders');

        return static::$navigationGroup;
    }

    public static function form(Schema $schema): Schema
    {
        return WishlistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WishlistsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWishlists::route('/'),
            'create' => CreateWishlist::route('/create'),
            'edit' => EditWishlist::route('/{record}/edit'),
        ];
    }
}
