<?php

namespace App\Filament\App\Resources\Codes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\Codes\CodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCodes extends ListRecords
{
    protected static string $resource = CodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
