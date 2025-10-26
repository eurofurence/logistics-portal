<?php

namespace App\Filament\Admin\Resources\DepartmentResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Admin\Resources\DepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading(function ($record): string {
                    return __('general.delete') . ': ' . $record->name;
                }),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
