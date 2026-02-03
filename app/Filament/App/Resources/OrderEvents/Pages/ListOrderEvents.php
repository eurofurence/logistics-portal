<?php

namespace App\Filament\App\Resources\OrderEvents\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\App\Resources\OrderEvents\OrderEventResource;

class ListOrderEvents extends ListRecords
{
    protected static string $resource = OrderEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
