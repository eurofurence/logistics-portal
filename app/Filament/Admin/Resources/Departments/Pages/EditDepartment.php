<?php

namespace App\Filament\Admin\Resources\Departments\Pages;

use App\Actions\LinkedRecordDeleteActions;
use App\Filament\Admin\Resources\Departments\DepartmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LinkedRecordDeleteActions::single(DeleteAction::make())
                ->modalHeading(function ($record): string {
                    return __('general.delete').': '.$record->name;
                }),
            LinkedRecordDeleteActions::single(ForceDeleteAction::make()),
            RestoreAction::make(),
        ];
    }
}
