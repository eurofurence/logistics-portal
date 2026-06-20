<?php

namespace App\Filament\Admin\Resources\TestModels\Pages;

use App\Filament\Admin\Resources\TestModels\TestModelResource;
use Filament\Actions\CreateAction;
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
