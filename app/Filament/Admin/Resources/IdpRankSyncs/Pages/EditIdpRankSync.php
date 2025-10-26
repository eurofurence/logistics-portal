<?php

namespace App\Filament\Admin\Resources\IdpRankSyncResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Admin\Resources\IdpRankSyncResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIdpRankSync extends EditRecord
{
    protected static string $resource = IdpRankSyncResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading(function ($record): string {
                    return __('general.delete') . ': ' . $record->name;
                }),
        ];
    }
}
