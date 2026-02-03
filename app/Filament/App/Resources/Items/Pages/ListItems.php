<?php

namespace App\Filament\App\Resources\Items\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\Items\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
