<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypes\Pages;

use App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypes\ContainerTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContainerType extends EditRecord
{
    protected static string $resource = ContainerTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
