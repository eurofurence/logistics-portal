<?php

namespace App\Filament\App\Resources\OrderEventResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\OrderEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrderEvent extends EditRecord
{
    protected static string $resource = OrderEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete') . ': ' . $record->name;
                }),
        ];
    }
}
