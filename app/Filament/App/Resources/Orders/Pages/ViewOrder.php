<?php

namespace App\Filament\App\Resources\OrderResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\App\Resources\OrderResource;
use App\Filament\App\Widgets\StatusTimelineWidget;

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
                    return __('general.delete') . ': ' . $record->name;
                }),
        ];
    }
}
