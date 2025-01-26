<?php

namespace App\Filament\Admin\Resources\WhitelistResource\Pages;

use App\Filament\Admin\Resources\WhitelistResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWhitelist extends CreateRecord
{
    protected static string $resource = WhitelistResource::class;

    public function getBreadcrumb(): string
    {
        return __('general.add_entry');
    }

    public function getTitle(): string
    {
        return __('general.add_entry');
    }

}
