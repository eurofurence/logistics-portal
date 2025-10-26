<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Admin\Resources\Users\UserResource;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading(function ($record): string {
                    return __('general.delete') . ': ' . $record->name;
                }),
            EditAction::make(),
        ];
    }
}
