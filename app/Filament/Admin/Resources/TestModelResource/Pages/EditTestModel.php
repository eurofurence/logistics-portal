<?php

namespace App\Filament\Admin\Resources\TestModelResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Admin\Resources\TestModelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTestModel extends EditRecord
{
    protected static string $resource = TestModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
