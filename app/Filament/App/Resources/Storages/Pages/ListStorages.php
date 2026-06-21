<?php

namespace App\Filament\App\Resources\Storages\Pages;

use App\Filament\App\Resources\Storages\StorageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStorages extends ListRecords
{
    protected static string $resource = StorageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
