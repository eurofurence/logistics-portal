<?php

namespace App\Filament\App\Resources\OrderResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\App\Resources\OrderResource;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use App\Filament\App\Resources\OrderResource\Widgets\OrderStats;

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
                ->label(__('general.all')),
            'open' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', ['open', 'processing']))
                ->label(__('general.open')),
            'ordered' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'ordered'))
                ->label(__('general.ordered')),
            'delivered' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'delivered'))
                ->label(__('general.delivered')),
            'received' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', ['received', 'partially_received']))
                ->label(__('general.received')),
            'rejected' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'rejected'))
                ->label(__('general.rejected')),
            'other' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', ['on_hold', 'checking', 'awaiting_approval', 'refunded', 'locked', ]))
                ->label(__('general.other')),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            OrderStats::class
        ];
    }
}
