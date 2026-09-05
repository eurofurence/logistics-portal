<?php

namespace App\Filament\App\Resources\Items;

use App\Filament\App\Resources\Items\Pages\CreateItem;
use App\Filament\App\Resources\Items\Pages\EditItem;
use App\Filament\App\Resources\Items\Pages\ListItems;
use App\Filament\App\Resources\Items\Pages\ViewItem;
use App\Filament\App\Resources\Items\Schemas\ItemForm;
use App\Filament\App\Resources\Items\Tables\ItemsTable;
use App\Models\Item;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.inventory');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.inventory_items');
    }

    public static function getModelLabel(): string
    {
        return __('general.inventory_item');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.inventory_items');
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'serialnumber', 'url', 'description', 'owner'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('general.name') => $record->name,
            __('general.department') => $record->connected_department->name,
            __('general.serialnumber') => $record->serialnumber,
            __('general.owner') => $record->owner,
            __('general.description') => $record->description,
            __('general.storage') => $record->connected_storage->name,
        ];
    }

    public static function isView(): bool
    {
        return request()->route()->getName() === 'filament.app.resources.items.view';
    }

    public static function isEdit(): bool
    {
        return request()->route()->getName() === 'filament.app.resources.items.edit';
    }

    public static function isCreate(): bool
    {
        return request()->route()->getName() === 'filament.app.resources.items.create';
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        if ($user->isSuperAdmin() || $user->can('can-see-all_items')) {
            return $query;
        }

        $accessibleDepartmentIds = $user->getDepartmentsWithPermission('view-Item')->pluck('id')->toArray();

        return $query->whereIn('department', $accessibleDepartmentIds);
    }

    public static function form(Schema $schema): Schema
    {
        return ItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItemsTable::configure($table);
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
            'index' => ListItems::route('/'),
            'create' => CreateItem::route('/create'),
            'edit' => EditItem::route('/{record}/edit'),
            'view' => ViewItem::route('/{record}'),
        ];
    }
}
