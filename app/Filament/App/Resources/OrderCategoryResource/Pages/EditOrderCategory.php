<?php

namespace App\Filament\App\Resources\OrderCategoryResource\Pages;

use App\Filament\App\Resources\OrderCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrderCategory extends EditRecord
{
    protected static string $resource = OrderCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ViewAction::make(),
        ];
    }
}
