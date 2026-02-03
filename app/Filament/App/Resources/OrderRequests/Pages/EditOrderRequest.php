<?php

namespace App\Filament\App\Resources\OrderRequests\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\App\Resources\OrderRequests\OrderRequestResource;

class EditOrderRequest extends EditRecord
{

    protected static string $resource = OrderRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete') . ': ' . $record->name;
                }),
            ViewAction::make()
                ->icon('heroicon-o-eye'),
        ];
    }
}
