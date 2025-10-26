<?php

namespace App\Filament\App\Resources\StorageResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\StorageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStorage extends EditRecord
{
    protected static string $resource = StorageResource::class;

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
