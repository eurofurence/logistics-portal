<?php

namespace App\Filament\App\Resources\StorageResource\Pages;

use Filament\Actions;
use App\Filament\App\Resources\StorageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewStorage extends ViewRecord
{

    protected static string $resource = StorageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
            ->icon('heroicon-o-pencil'),
        ];
    }
}
