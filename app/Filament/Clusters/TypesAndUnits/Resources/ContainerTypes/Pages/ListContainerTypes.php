<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypes\Pages;

use App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypes\ContainerTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContainerTypes extends ListRecords
{
    protected static string $resource = ContainerTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
