<?php

namespace App\Filament\App\Resources\Items\Pages;

use App\Filament\App\Resources\Items\ItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;
}
