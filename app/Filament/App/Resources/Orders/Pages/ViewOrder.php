<?php

namespace App\Filament\App\Resources\Orders\Pages;

use App\Filament\App\Resources\Orders\OrderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-o-pencil'),
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete').': '.$record->name;
                }),
        ];
    }
}
