<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\SubUnitResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Clusters\TypesAndUnits\Resources\SubUnitResource;

class EditSubUnit extends EditRecord
{
    protected static string $resource = SubUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
