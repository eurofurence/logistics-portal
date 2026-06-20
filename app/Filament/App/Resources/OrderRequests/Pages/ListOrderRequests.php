<?php

namespace App\Filament\App\Resources\OrderRequests\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\OrderRequests\OrderRequestResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrderRequests extends ListRecords
{
    protected static string $resource = OrderRequestResource::class;

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
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 0))
                ->label(__('general.open')),
            'processing' => Tab::make()
                ->icon('heroicon-o-arrow-path')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 2))
                ->label(__('general.processing')),
            'finished' => Tab::make()
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 1))
                ->label(__('general.finished')),
            'rejected' => Tab::make()
                ->icon('heroicon-o-no-symbol')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 5))
                ->label(__('general.rejected')),
            'other' => Tab::make()
                ->icon('heroicon-o-ellipsis-horizontal')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', [3, 4]))
                ->label(__('general.other')),
        ];
    }
}
