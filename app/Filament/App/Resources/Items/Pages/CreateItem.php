<?php

namespace App\Filament\App\Resources\ItemResource\Pages;

use App\Filament\App\Resources\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;
}
