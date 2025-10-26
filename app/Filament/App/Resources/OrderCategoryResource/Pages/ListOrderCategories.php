<?php

namespace App\Filament\App\Resources\OrderCategoryResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\OrderCategoryResource;
use Filament\Actions;
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
