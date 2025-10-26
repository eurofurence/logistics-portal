<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Admin\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
