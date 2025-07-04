<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-Item') || $user->hasAnyDepartmentRoleWithPermissionTo('view-any-Item');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Item $item): bool
    {
        return $user->checkPermissionTo('view-Item') || $user->hasDepartmentRoleWithPermissionTo('view-Item', $item->department) ;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-Item') || $user->hasAnyDepartmentRoleWithPermissionTo('create-Item');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Item $item): bool
    {
        return $user->checkPermissionTo('update-Item') || $user->hasDepartmentRoleWithPermissionTo('update-Item', $item->department);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Item $item): bool
    {
        return $user->checkPermissionTo('delete-Item')  || $user->hasDepartmentRoleWithPermissionTo('delete-Storage', $item->department);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): bool
    {
        return $user->checkPermissionTo('restore-Item') || $user->hasAnyDepartmentRoleWithPermissionTo('restore-Item');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-Item');
    }

    /**
     * Determine whether the user can permanently delete the model. (Many models at once)
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-Item');
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-delete-Item');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-Item');
    }
}
