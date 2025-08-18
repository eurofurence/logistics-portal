<?php

namespace App\Policies;

use App\Models\Bill;
use App\Models\User;
use App\Models\OrderEvent;

class BillPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasAnyDepartmentRoleWithPermissionTo('view-any-Bill') || $user->checkPermissionTo('can-see-all-bills');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Bill $bill): bool
    {
        return $user->isSuperAdmin() || $user->checkPermissionTo('can-see-all-bills') || $user->hasDepartmentRoleWithPermissionTo('view-Bill', $bill->department_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $event_counter = OrderEvent::all(['id'])->count();

        return $user->hasAnyDepartmentRoleWithPermissionTo('create-Bill') && ($event_counter > 0);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Bill $bill): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $result = false;

        if ($bill->status == 'open' || $user->checkPermissionTo('can-always-edit-bills')) {
            $result = true;
        }

        return ($user->hasDepartmentRoleWithPermissionTo('update-Bill', $bill->department_id) || $user->checkPermissionTo('can-edit-all-bills')) && $result;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Bill $bill): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $result = false;

        if ($bill->status == 'open' || $user->checkPermissionTo('can-always-delete-bills')) {
            $result = true;
        }

        return ($user->hasDepartmentRoleWithPermissionTo('delete-Bill', $bill->department_id) || $user->checkPermissionTo('can-delete-all-bills')) && $result;
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyDepartmentRoleWithPermissionTo('restore-Bill') || $user->checkPermissionTo('can-restore-all-bills');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, Bill $bill): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        return $user->hasAnyDepartmentRoleWithPermissionTo('replicate-Bill');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Bill $bill): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function bulkForceDelete(User $user, Bill $bill): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user, Bill $bill): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user, Bill $bill): bool
    {
        return $user->isSuperAdmin();
    }
}
