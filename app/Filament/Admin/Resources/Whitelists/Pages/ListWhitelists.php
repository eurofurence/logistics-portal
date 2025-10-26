<?php

namespace App\Filament\Admin\Resources\WhitelistResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Admin\Resources\WhitelistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWhitelists extends ListRecords
{
    protected static string $resource = WhitelistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
