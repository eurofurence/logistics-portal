<?php

namespace App\Filament\App\Resources\Codes\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\Codes\CodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCode extends EditRecord
{
    protected static string $resource = CodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
