<?php

namespace App\Filament\App\Resources\BillResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\App\Resources\BillResource;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use App\Filament\App\Resources\BillResource\Widgets\BillStats;

class ListBills extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
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
}
