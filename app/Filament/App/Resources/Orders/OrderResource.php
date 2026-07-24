<?php

namespace App\Filament\App\Resources\Orders;

use App\Filament\App\Resources\Orders\Pages\CreateOrder;
use App\Filament\App\Resources\Orders\Pages\EditOrder;
use App\Filament\App\Resources\Orders\Pages\ListOrders;
use App\Filament\App\Resources\Orders\Pages\ViewOrder;
use App\Filament\App\Resources\Orders\Schemas\OrderForm;
use App\Filament\App\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.orders');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.orders');
    }

    public static function getModelLabel(): string
    {
        return __('general.order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.orders');
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('general.department') => $record->department->name,
            __('general.order_event') => $record->event->name,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'event',
                'department',
                'directoryArticle',
                'orderRequest',
                'addedBy',
                'editedBy',
                'approvedBy',
            ]);
        $user = Auth::user();

        $query->when(! $user->can('can-see-all-orders'), function ($query) use ($user) {
            return $query->whereIn('department_id', $user->getDepartmentsWithPermission('view-Order')->pluck('id'));
        });

        return $query;
    }

    /**
     * Checks if the current request route corresponds to the order view page.
     *
     * This static method determines whether the current route name matches
     * the specific route used for viewing an order in the Filament application.
     *
     * @return bool Returns true if the current route is the order view page, false otherwise.
     */
    public static function isView(): bool
    {
        return request()->route()->getName() === 'filament.app.resources.orders.view';
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
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
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
