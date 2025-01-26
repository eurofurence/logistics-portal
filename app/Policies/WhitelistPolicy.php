<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Auth\Access\Response;

class WhitelistPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-Whitelist');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Whitelist $whitelist): bool
    {
        return $user->checkPermissionTo('view-Whitelist');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-Whitelist');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Whitelist $whitelist): bool
    {
        return $user->checkPermissionTo('update-Whitelist');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Whitelist $whitelist): bool
    {
        return $user->checkPermissionTo('delete-Whitelist');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Whitelist $whitelist): bool
    {
        return $user->checkPermissionTo('restore-Whitelist');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Whitelist $whitelist): bool
    {
        return $user->checkPermissionTo('force-delete-Whitelist');
    }

    /**
     * Determine whether the user can permanently delete the model via bulk action.
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-Whitelist');
    }

    /**
     * Determine whether the user can delete the model via bulk action.
     */
    public function bulkDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-delete-Whitelist');
    }

    /**
     * Determine whether the user can restore the model via bulk action.
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-Whitelist');
    }
}
