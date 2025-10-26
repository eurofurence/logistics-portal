<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\BaseUnitResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\TypesAndUnits\Resources\BaseUnitResource;
use Filament\Actions;
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
