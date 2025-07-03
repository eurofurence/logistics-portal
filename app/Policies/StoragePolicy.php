<?php

namespace App\Policies;

use App\Models\Storage;
use App\Models\User;

class StoragePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-Storage')  || $user->hasAnyDepartmentRoleWithPermissionTo('view-any-Storage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Storage $storage): bool
    {
        switch ($storage->type) {
            case 0:
                return false;
                break;

            case 1:
                return true;
                break;

            case 2:
                if ($user->hasDepartmentRoleWithPermissionTo('view-Storage', $storage->managing_department) || $user->checkPermissionTo('can-see-all-storages')) {
                    return true;
                }
                break;

            default:
                return false;
                break;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-Storage') || $user->hasAnyDepartmentRoleWithPermissionTo('create-Storage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Storage $storage): bool
    {
        return $user->checkPermissionTo('update-Storage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Storage $storage): bool
    {
        return $user->checkPermissionTo('delete-Storage');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Storage $storage): bool
    {
        return $user->checkPermissionTo('restore-Storage');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Storage $storage): bool
    {
        return $user->checkPermissionTo('force-delete-Storage');
    }

    /**
     * Determine whether the user can permanently delete the model via bulk action.
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-Storage');
    }

    /**
     * Determine whether the user can delete the model via bulk action.
     */
    public function bulkDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-delete-Storage');
    }

    /**
     * Determine whether the user can restore the model via bulk action.
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-Storage');
    }
}
