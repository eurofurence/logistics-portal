<?php

namespace App\Filament\Admin\Resources\TestModels\Pages;

use App\Filament\Admin\Resources\TestModels\TestModelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTestModel extends CreateRecord
{
    protected static string $resource = TestModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
