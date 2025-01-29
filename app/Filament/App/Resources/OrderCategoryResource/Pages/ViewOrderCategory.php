<?php

namespace App\Filament\App\Resources\OrderCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\App\Resources\OrderCategoryResource;

class ViewOrderCategory extends ViewRecord
{
    protected static string $resource = OrderCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
