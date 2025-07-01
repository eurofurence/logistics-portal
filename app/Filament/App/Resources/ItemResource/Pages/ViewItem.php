<?php

namespace App\Filament\App\Resources\ItemResource\Pages;

use Filament\Actions;
use App\Filament\App\Resources\ItemResource;
use Filament\Resources\Pages\ViewRecord;

class ViewItem extends ViewRecord
{

    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }
}
