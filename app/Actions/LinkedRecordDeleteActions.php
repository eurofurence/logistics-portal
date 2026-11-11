<?php

namespace App\Actions;

use App\Models\Department;
use App\Models\OrderEvent;
use App\Services\LinkedRecordDeletionGuard;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class LinkedRecordDeleteActions
{
    public static function single(DeleteAction|ForceDeleteAction $action): DeleteAction|ForceDeleteAction
    {
        return $action
            ->disabled(fn (Department|OrderEvent $record): bool => app(LinkedRecordDeletionGuard::class)->hasLinks($record))
            ->tooltip(fn (Department|OrderEvent $record): ?string => app(LinkedRecordDeletionGuard::class)->hasLinks($record) ? __('general.linked_record_delete_blocked') : null)
            ->failureNotificationTitle(__('general.linked_record_delete_blocked'));
    }

    public static function bulk(DeleteBulkAction $action): DeleteBulkAction
    {
        return $action->using(function (DeleteBulkAction $action, Collection|LazyCollection $records): void {
            foreach ($records as $record) {
                if (! $record->delete()) {
                    $action->reportBulkProcessingFailure('linked_records', fn (int $failureCount): string => __('general.linked_records_skipped', ['count' => $failureCount]));
                }
            }
        });
    }
}
