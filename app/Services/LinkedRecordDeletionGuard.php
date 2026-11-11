<?php

namespace App\Services;

use App\Models\Department;
use App\Models\OrderEvent;

class LinkedRecordDeletionGuard
{
    public function hasLinks(Department|OrderEvent $record): bool
    {
        $column = $record instanceof Department ? 'department_id' : 'order_event_id';
        $references = [
            ['orders', $column],
            ['order_requests', $column],
            ['bills', $column],
        ];

        if ($record instanceof Department) {
            $references = [
                ...$references,
                ['department_user', 'department_id'],
                ['items', 'department'],
                ['items_operations_sites', 'department'],
                ['inventory_sub_category', 'department'],
                ['storages', 'managing_department'],
                ['storage_department', 'department'],
                ['wishlist_department', 'department_id'],
            ];
        } else {
            $references[] = ['order_event_notification_deliveries', 'order_event_id'];
        }

        foreach ($references as [$table, $foreignKey]) {
            if ($record->getConnection()->table($table)->where($foreignKey, $record->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }
}
