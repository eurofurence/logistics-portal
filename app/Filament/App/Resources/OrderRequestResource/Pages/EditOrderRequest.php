<?php

namespace App\Filament\App\Resources\OrderRequestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\App\Resources\OrderRequestResource;

class EditOrderRequest extends EditRecord
{

    protected static string $resource = OrderRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete') . ': ' . $record->name;
                }),
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye'),
        ];
    }
}
