<?php

namespace App\Filament\App\Resources\Storages\Pages;

use App\Filament\App\Resources\Storages\StorageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStorage extends CreateRecord
{
    protected static string $resource = StorageResource::class;
}
