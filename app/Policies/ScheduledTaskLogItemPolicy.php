<?php

namespace App\Policies;

use App\Models\ScheduledTaskLogItem;
use App\Models\User;

class ScheduledTaskLogItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-ScheduledTaskLogItem');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ScheduledTaskLogItem $scheduledtasklogitem): bool
    {
        return $user->checkPermissionTo('view-ScheduledTaskLogItem');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-ScheduledTaskLogItem');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ScheduledTaskLogItem $scheduledtasklogitem): bool
    {
        return $user->checkPermissionTo('update-ScheduledTaskLogItem');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ScheduledTaskLogItem $scheduledtasklogitem): bool
    {
        return $user->checkPermissionTo('delete-ScheduledTaskLogItem');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ScheduledTaskLogItem $scheduledtasklogitem): bool
    {
        return $user->checkPermissionTo('restore-ScheduledTaskLogItem');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ScheduledTaskLogItem $scheduledtasklogitem): bool
    {
        return $user->checkPermissionTo('force-delete-ScheduledTaskLogItem');
    }
}
