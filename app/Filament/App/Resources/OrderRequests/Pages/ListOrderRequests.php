<?php

namespace App\Filament\App\Resources\OrderRequests\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\OrderRequests\OrderRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

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
}
