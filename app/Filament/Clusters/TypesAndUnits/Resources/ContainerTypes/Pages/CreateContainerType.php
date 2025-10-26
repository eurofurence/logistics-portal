<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypes\ContainerTypeResource;

class CreateContainerType extends CreateRecord
{
    protected static string $resource = ContainerTypeResource::class;
}
