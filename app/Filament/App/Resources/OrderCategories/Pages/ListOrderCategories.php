<?php

namespace App\Filament\App\Resources\OrderCategories\Pages;

use App\Filament\App\Resources\OrderCategories\OrderCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrderCategories extends ListRecords
{
    protected static string $resource = OrderCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
