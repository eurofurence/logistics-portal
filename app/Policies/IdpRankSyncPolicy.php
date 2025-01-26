<?php

namespace App\Policies;

use App\Models\IdpRankSync;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class IdpRankSyncPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-IdpRankSync');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, IdpRankSync $idpRankSync): bool
    {
        return $user->checkPermissionTo('view-IdpRankSync');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-IdpRankSync');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, IdpRankSync $idpRankSync): bool
    {
        return $user->checkPermissionTo('update-IdpRankSync');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, IdpRankSync $idpRankSync): bool
    {
        return $user->checkPermissionTo('delete-IdpRankSync');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, IdpRankSync $idpRankSync): bool
    {
        return $user->checkPermissionTo('restore-IdpRankSync'); //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, IdpRankSync $idpRankSync): bool
    {
        return $user->checkPermissionTo('force-delete-IdpRankSync');
    }

    /**
     * Determine whether the user can permanently delete the model. (Many models at once)
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-IdpRankSync');
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-delete-IdpRankSync');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-IdpRankSync');
    }
}
