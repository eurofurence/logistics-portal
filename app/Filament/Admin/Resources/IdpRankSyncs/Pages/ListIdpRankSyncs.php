<?php

namespace App\Filament\Admin\Resources\IdpRankSyncs\Pages;

use App\Filament\Admin\Resources\IdpRankSyncs\IdpRankSyncResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIdpRankSyncs extends ListRecords
{
    protected static string $resource = IdpRankSyncResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
