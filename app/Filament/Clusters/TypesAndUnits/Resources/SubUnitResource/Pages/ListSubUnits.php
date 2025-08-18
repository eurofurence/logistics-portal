<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\SubUnitResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Clusters\TypesAndUnits\Resources\SubUnitResource;

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
