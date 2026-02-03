<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypes\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypes\ContainerTypeResource;

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
