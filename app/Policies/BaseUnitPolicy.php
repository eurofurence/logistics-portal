<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\BaseUnit;
use App\Models\User;

class BaseUnitPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-BaseUnit');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BaseUnit $baseunit): bool
    {
        return $user->checkPermissionTo('view-BaseUnit');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-BaseUnit');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BaseUnit $baseunit): bool
    {
        return $user->checkPermissionTo('update-BaseUnit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BaseUnit $baseunit): bool
    {
        return $user->checkPermissionTo('delete-BaseUnit');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BaseUnit $baseunit): bool
    {
        return $user->checkPermissionTo('restore-BaseUnit');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BaseUnit $baseunit): bool
    {
        return $user->checkPermissionTo('force-delete-BaseUnit');
    }

    /**
     * Determine whether the user can permanently delete the model. (Many models at once)
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-BaseUnit');
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-delete-BaseUnit');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-BaseUnit');
    }
}
