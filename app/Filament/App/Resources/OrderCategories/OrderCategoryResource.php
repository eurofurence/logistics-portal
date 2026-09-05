<?php

namespace App\Filament\App\Resources\OrderCategories;

use App\Filament\App\Resources\OrderCategories\Pages\CreateOrderCategory;
use App\Filament\App\Resources\OrderCategories\Pages\EditOrderCategory;
use App\Filament\App\Resources\OrderCategories\Pages\ListOrderCategories;
use App\Filament\App\Resources\OrderCategories\Pages\ViewOrderCategory;
use App\Filament\App\Resources\OrderCategories\Schemas\OrderCategoryForm;
use App\Filament\App\Resources\OrderCategories\Tables\OrderCategoriesTable;
use App\Models\OrderCategory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class OrderCategoryResource extends Resource
{
    protected static ?string $model = OrderCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.orders');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.categories');
    }

    public static function getModelLabel(): string
    {
        return __('general.category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.categories');
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function form(Schema $schema): Schema
    {
        return OrderCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderCategories::route('/'),
            'create' => CreateOrderCategory::route('/create'),
            'edit' => EditOrderCategory::route('/{record}/edit'),
            'view' => ViewOrderCategory::route('/{record}'),
        ];
    }
}
