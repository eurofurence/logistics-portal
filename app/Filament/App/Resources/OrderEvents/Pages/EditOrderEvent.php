<?php

namespace App\Filament\App\Resources\OrderEvents\Pages;

use App\Actions\LinkedRecordDeleteActions;
use App\Filament\App\Resources\OrderEvents\OrderEventResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrderEvent extends EditRecord
{
    protected static string $resource = OrderEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LinkedRecordDeleteActions::single(DeleteAction::make())
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete').': '.$record->name;
                }),
        ];
    }
}
