<?php

namespace App\Filament\App\Resources\Orders\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\App\Resources\Orders\OrderResource;

class EditOrder extends EditRecord
{

    protected static string $resource = OrderResource::class;

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
