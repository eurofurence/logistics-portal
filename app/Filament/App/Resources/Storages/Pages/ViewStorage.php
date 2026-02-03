<?php

namespace App\Filament\App\Resources\Storages\Pages;

use Filament\Actions\EditAction;
use Filament\Actions;
use App\Filament\App\Resources\Storages\StorageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewStorage extends ViewRecord
{

    protected static string $resource = StorageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
            ->icon('heroicon-o-pencil'),
        ];
    }
}
