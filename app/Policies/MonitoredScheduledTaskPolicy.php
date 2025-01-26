<?php

namespace App\Policies;

use App\Models\MonitoredScheduledTask;
use App\Models\User;

class MonitoredScheduledTaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-MonitoredScheduledTask');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MonitoredScheduledTask $monitoredscheduledtask): bool
    {
        return $user->checkPermissionTo('view-MonitoredScheduledTask');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-MonitoredScheduledTask');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MonitoredScheduledTask $monitoredscheduledtask): bool
    {
        return $user->checkPermissionTo('update-MonitoredScheduledTask');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MonitoredScheduledTask $monitoredscheduledtask): bool
    {
        return $user->checkPermissionTo('delete-MonitoredScheduledTask');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MonitoredScheduledTask $monitoredscheduledtask): bool
    {
        return $user->checkPermissionTo('restore-MonitoredScheduledTask');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MonitoredScheduledTask $monitoredscheduledtask): bool
    {
        return $user->checkPermissionTo('force-delete-MonitoredScheduledTask');
    }
}
