<?php

namespace App\Filament\App\Resources\OrderRequestResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\OrderRequestResource;
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
