<?php

namespace App\Filament\Admin\Resources\IdpRankSyncResource\Pages;

use App\Filament\Admin\Resources\IdpRankSyncResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIdpRankSyncs extends ListRecords
{
    protected static string $resource = IdpRankSyncResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
