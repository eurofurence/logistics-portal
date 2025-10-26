<?php

namespace App\Filament\Admin\Resources\TestModelResource\Pages;

use Filament\Actions;
use RuntimeException;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Resources\TestModelResource;

class CreateTestModel extends CreateRecord
{
    protected static string $resource = TestModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
