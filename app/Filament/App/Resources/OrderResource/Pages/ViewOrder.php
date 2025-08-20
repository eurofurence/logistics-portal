<?php

namespace App\Filament\App\Resources\OrderResource\Pages;

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
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete') . ': ' . $record->name;
                }),
        ];
    }
}
