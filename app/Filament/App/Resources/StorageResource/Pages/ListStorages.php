<?php

namespace App\Filament\App\Resources\StorageResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\StorageResource;
use Filament\Actions;
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
