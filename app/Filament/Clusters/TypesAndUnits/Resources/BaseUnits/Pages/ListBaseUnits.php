<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\BaseUnits\Pages;

use App\Filament\Clusters\TypesAndUnits\Resources\BaseUnits\BaseUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBaseUnits extends ListRecords
{
    protected static string $resource = BaseUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
