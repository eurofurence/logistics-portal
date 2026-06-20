<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\SubUnits\Pages;

use App\Filament\Clusters\TypesAndUnits\Resources\SubUnits\SubUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubUnit extends EditRecord
{
    protected static string $resource = SubUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
