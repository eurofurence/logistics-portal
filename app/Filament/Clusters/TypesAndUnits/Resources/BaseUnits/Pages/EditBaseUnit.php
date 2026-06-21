<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\BaseUnits\Pages;

use App\Filament\Clusters\TypesAndUnits\Resources\BaseUnits\BaseUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBaseUnit extends EditRecord
{
    protected static string $resource = BaseUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
