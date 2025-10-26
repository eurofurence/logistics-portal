<?php

namespace App\Filament\Admin\Resources\WhitelistResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Admin\Resources\WhitelistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWhitelist extends EditRecord
{
    protected static string $resource = WhitelistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
