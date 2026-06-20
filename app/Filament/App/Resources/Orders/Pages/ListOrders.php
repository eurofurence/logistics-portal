<?php

namespace App\Filament\App\Resources\Orders\Pages;

use App\Filament\App\Resources\Orders\OrderResource;
use App\Filament\App\Resources\Orders\Widgets\OrderStats;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->icon('heroicon-o-queue-list')
                ->label(__('general.all')),
            'open' => Tab::make()
                ->icon('heroicon-o-arrow-path')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['open', 'processing']))
                ->label(__('general.open')),
            'ordered' => Tab::make()
                ->icon('heroicon-o-shopping-cart')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'ordered'))
                ->label(__('general.ordered')),
            'delivered' => Tab::make()
                ->icon('heroicon-o-truck')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'delivered'))
                ->label(__('general.delivered')),
            'received' => Tab::make()
                ->icon('heroicon-o-check')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['received', 'partially_received']))
                ->label(__('general.received')),
            'rejected' => Tab::make()
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->label(__('general.rejected')),
            'other' => Tab::make()
                ->icon('heroicon-o-ellipsis-horizontal')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['on_hold', 'checking', 'awaiting_approval', 'refunded', 'locked']))
                ->label(__('general.other')),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            OrderStats::class,
        ];
    }
}
