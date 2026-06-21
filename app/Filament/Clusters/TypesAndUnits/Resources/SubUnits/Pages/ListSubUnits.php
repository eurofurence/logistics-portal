<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\SubUnits\Pages;

use App\Filament\Clusters\TypesAndUnits\Resources\SubUnits\SubUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubUnits extends ListRecords
{
    protected static string $resource = SubUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
