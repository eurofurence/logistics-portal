<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypes\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypes\ContainerTypeResource;

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
