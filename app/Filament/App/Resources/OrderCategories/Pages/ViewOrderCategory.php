<?php

namespace App\Filament\App\Resources\OrderCategories\Pages;

use App\Filament\App\Resources\OrderCategories\OrderCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrderCategory extends ViewRecord
{
    protected static string $resource = OrderCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
