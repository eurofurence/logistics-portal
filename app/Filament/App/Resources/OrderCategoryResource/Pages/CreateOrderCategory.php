<?php

namespace App\Filament\App\Resources\OrderCategoryResource\Pages;

use App\Filament\App\Resources\OrderCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderCategory extends CreateRecord
{
    protected static string $resource = OrderCategoryResource::class;
}
