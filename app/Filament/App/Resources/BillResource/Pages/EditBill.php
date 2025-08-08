<?php

namespace App\Filament\App\Resources\BillResource\Pages;

use App\Filament\App\Resources\BillResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBill extends EditRecord
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete') . ': ' . $record->title;
                }),
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye'),
        ];
    }
}
