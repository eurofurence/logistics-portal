<?php

namespace App\Filament\Admin\Resources\IdpRankSyncs\Pages;

use App\Filament\Admin\Resources\IdpRankSyncs\IdpRankSyncResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIdpRankSync extends CreateRecord
{
    protected static string $resource = IdpRankSyncResource::class;
}
