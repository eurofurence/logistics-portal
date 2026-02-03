<?php

namespace App\Filament\App\Resources\OrderCategories\Pages;

use App\Filament\App\Resources\OrderCategories\OrderCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderCategory extends CreateRecord
{
    protected static string $resource = OrderCategoryResource::class;
}
