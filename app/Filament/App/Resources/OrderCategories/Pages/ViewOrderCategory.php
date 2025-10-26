<?php

namespace App\Filament\App\Resources\OrderCategories\Pages;

use Filament\Actions\EditAction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\App\Resources\OrderCategories\OrderCategoryResource;

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
