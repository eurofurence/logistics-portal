<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    #TODO: $user->isSuperAdmin() überall einbauen
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasAnyDepartmentRoleWithPermissionTo('view-any-Item');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Item $item): bool
    {
        return $user->isSuperAdmin() || $user->checkPermissionTo('can-see-all_items') || $user->hasDepartmentRoleWithPermissionTo('view-Item', $item->department);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        #TODO: Permission for create for other departments
        return $user->isSuperAdmin() || $user->hasAnyDepartmentRoleWithPermissionTo('create-Item');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Item $item): bool
    {
        return $user->isSuperAdmin() || $user->hasDepartmentRoleWithPermissionTo('update-Item', $item->department);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Item $item): bool
    {
        return $user->isSuperAdmin() || $user->hasDepartmentRoleWithPermissionTo('delete-Storage', $item->department);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasAnyDepartmentRoleWithPermissionTo('restore-Item');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, Item $item): bool
    {
        if ($user->isSuperAdmin() || $user->hasDepartmentRoleWithPermissionTo('replicate-Item', $item->department)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user): bool
    {
        return $user->isSuperAdmin();//$user->checkPermissionTo('force-delete-Item');
    }

    /**
     * Determine whether the user can permanently delete the model. (Many models at once)
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->isSuperAdmin(); //$user->checkPermissionTo('bulk-force-delete-Item');
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user): bool
    {
        return $user->isSuperAdmin(); //$user->checkPermissionTo('bulk-delete-Item');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user): bool
    {
        return $user->isSuperAdmin(); //$user->checkPermissionTo('bulk-restore-Item');
    }
}
