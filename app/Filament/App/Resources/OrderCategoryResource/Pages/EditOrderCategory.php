<?php

namespace App\Filament\App\Resources\OrderCategoryResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use App\Filament\App\Resources\OrderCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrderCategory extends EditRecord
{
    protected static string $resource = OrderCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete') . ': ' . $record->name;
                }),
            ViewAction::make(),
        ];
    }
}
