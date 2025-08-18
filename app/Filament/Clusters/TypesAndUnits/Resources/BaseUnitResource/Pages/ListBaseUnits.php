<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\BaseUnitResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\TypesAndUnits\Resources\BaseUnitResource;
use Filament\Actions;
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
