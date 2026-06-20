<?php

namespace App\Filament\Admin\Resources\IdpRankSyncs\Pages;

use App\Filament\Admin\Resources\IdpRankSyncs\IdpRankSyncResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIdpRankSync extends EditRecord
{
    protected static string $resource = IdpRankSyncResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading(function ($record): string {
                    return __('general.delete').': '.$record->name;
                }),
        ];
    }
}
