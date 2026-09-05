<?php

namespace App\Filament\App\Resources\OrderRequests;

use App\Filament\App\Resources\OrderRequests\Pages\CreateOrderRequest;
use App\Filament\App\Resources\OrderRequests\Pages\EditOrderRequest;
use App\Filament\App\Resources\OrderRequests\Pages\ListOrderRequests;
use App\Filament\App\Resources\OrderRequests\Pages\ViewOrderRequest;
use App\Filament\App\Resources\OrderRequests\Schemas\OrderRequestForm;
use App\Filament\App\Resources\OrderRequests\Schemas\OrderRequestInfolist;
use App\Filament\App\Resources\OrderRequests\Tables\OrderRequestsTable;
use App\Models\OrderRequest;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OrderRequestResource extends Resource
{
    protected static ?string $model = OrderRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.orders');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.my_order_request');
    }

    public static function getModelLabel(): string
    {
        return __('general.order_request');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.order_requests');
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->title;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'url'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('general.department') => $record->department->name,
            __('general.order_event') => $record->event->name,
            __('general.created_at') => $record->created_at,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        if (static::canViewAny()) {
            // Counting entries based on the active status of the relationship
            // Status 0 = open
            if (Auth::check()) {
                if (Auth::user()->can('can-moderate-order-request')) {
                    $counter = static::getModel()::whereIn('status', [0, 2, 4])->whereHas('event', function ($query) {
                        $query->where('is_active', true);
                    })->count();

                    return $counter > 0 ? $counter : null;
                }
            }
        }

        return null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        $query->when(! $user->can('can-see-all-orderRequests'), function ($query) use ($user) {
            return $query->whereIn('department_id', $user->getDepartmentsWithPermission('view-OrderRequest')->pluck('id'));
        });

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return OrderRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderRequestsTable::configure($table);
    }

    protected function getTableRecordUrlUsing(): ?callable
    {
        return fn ($record) => $this->getResource()::getUrl('view', ['record' => $record]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderRequestInfolist::configure($schema);
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
            'index' => ListOrderRequests::route('/'),
            'create' => CreateOrderRequest::route('/create'),
            'edit' => EditOrderRequest::route('/{record}/edit'),
            'view' => ViewOrderRequest::route('/{record}'),
        ];
    }
}
