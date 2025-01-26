<?php

namespace App\Policies;

use App\Models\OrderCategory;
use App\Models\User;

class OrderCategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-OrderCategory');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OrderCategory $OrderCategory): bool
    {
        return $user->checkPermissionTo('view-OrderCategory');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-OrderCategory');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OrderCategory $OrderCategory): bool
    {
        return $user->checkPermissionTo('update-OrderCategory');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OrderCategory $OrderCategory): bool
    {
        return $user->checkPermissionTo('delete-OrderCategory');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OrderCategory $OrderCategory): bool
    {
        return $user->checkPermissionTo('restore-OrderCategory');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OrderCategory $OrderCategory): bool
    {
        return $user->checkPermissionTo('force-delete-OrderCategory');
    }

    /**
     * Determine whether the user can permanently delete the model via bulk action.
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-OrderCategory');
    }

    /**
     * Determine whether the user can delete the model via bulk action.
     */
    public function bulkDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-delete-OrderCategory');
    }

    /**
     * Determine whether the user can restore the model via bulk action.
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-OrderCategory');
    }
}
