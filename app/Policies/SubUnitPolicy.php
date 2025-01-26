<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\SubUnit;
use App\Models\User;

class SubUnitPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-SubUnit');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SubUnit $subunit): bool
    {
        return $user->checkPermissionTo('view-SubUnit');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-SubUnit');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SubUnit $subunit): bool
    {
        return $user->checkPermissionTo('update-SubUnit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SubUnit $subunit): bool
    {
        return $user->checkPermissionTo('delete-SubUnit');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SubUnit $subunit): bool
    {
        return $user->checkPermissionTo('restore-SubUnit');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SubUnit $subunit): bool
    {
        return $user->checkPermissionTo('force-delete-SubUnit');
    }

    /**
     * Determine whether the user can permanently delete the model via bulk action.
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-SubUnit');
    }

    /**
     * Determine whether the user can delete the model via bulk action.
     */
    public function bulkDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-delete-SubUnit');
    }

    /**
     * Determine whether the user can restore the model via bulk action.
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-SubUnit');
    }
}
