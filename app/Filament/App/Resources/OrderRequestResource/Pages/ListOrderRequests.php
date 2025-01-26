<?php

namespace App\Filament\App\Resources\OrderRequestResource\Pages;

use App\Filament\App\Resources\OrderRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrderRequests extends ListRecords
{
    protected static string $resource = OrderRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
