<?php

namespace App\Filament\Admin\Resources\TestModelResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Admin\Resources\TestModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTestModels extends ListRecords
{
    protected static string $resource = TestModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
