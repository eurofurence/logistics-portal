<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypeResource;

class EditContainerType extends EditRecord
{
    protected static string $resource = ContainerTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
