<?php

namespace App\Filament\Admin\Resources\Whitelists\Pages;

use App\Filament\Admin\Resources\Whitelists\WhitelistResource;
use Filament\Actions\DeleteAction;
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
