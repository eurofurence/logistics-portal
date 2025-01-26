<?php

namespace App\Filament\App\Resources\OrderRequestResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use App\Filament\App\Resources\OrderRequestResource;

class EditOrderRequest extends EditRecord
{

    protected static string $resource = OrderRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
            ->icon('heroicon-o-trash'),
            Actions\ViewAction::make()
            ->icon('heroicon-o-eye'),
        ];
    }
}
