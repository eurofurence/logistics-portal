<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\StorageArea;
use App\Models\User;

class StorageAreaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-StorageArea');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StorageArea $storagearea): bool
    {
        return $user->checkPermissionTo('view-StorageArea');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-StorageArea');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StorageArea $storagearea): bool
    {
        return $user->checkPermissionTo('update-StorageArea');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StorageArea $storagearea): bool
    {
        return $user->checkPermissionTo('delete-StorageArea');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StorageArea $storagearea): bool
    {
        return $user->checkPermissionTo('restore-StorageArea');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StorageArea $storagearea): bool
    {
        return $user->checkPermissionTo('force-delete-StorageArea');
    }

    /**
     * Determine whether the user can permanently delete the model via bulk action.
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-StorageArea');
    }

    /**
     * Determine whether the user can delete the model via bulk action.
     */
    public function bulkDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-delete-StorageArea');
    }

    /**
     * Determine whether the user can restore the model via bulk action.
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-StorageArea');
    }
}
