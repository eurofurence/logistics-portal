<?php

namespace App\Filament\App\Resources\Bills\Pages;

use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\App\Resources\Bills\BillResource;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use App\Filament\App\Resources\Bills\Widgets\BillStats;

class ListBills extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('general.submit'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            BillStats::class
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label(__('general.all')),
            'open' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'open'))
                ->label(__('general.open'))
                ->icon('heroicon-o-document-currency-dollar'),
            'processing' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'processing'))
                ->label(__('general.processing'))
                ->icon('heroicon-o-arrow-path'),
            'done' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'done'))
                ->label(__('general.done'))
                ->icon('heroicon-o-check'),
            'rejected' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'rejected'))
                ->label(__('general.rejected'))
                ->icon('heroicon-o-x-circle'),
            'other' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotIn('status', ['open', 'processing', 'done', 'rejected']))
                ->label(__('general.other')),
        ];
    }
}
