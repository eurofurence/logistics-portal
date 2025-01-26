<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\StorageContainer;
use App\Models\User;

class StorageContainerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-StorageContainer');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StorageContainer $storagecontainer): bool
    {
        return $user->checkPermissionTo('view-StorageContainer');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-StorageContainer');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StorageContainer $storagecontainer): bool
    {
        return $user->checkPermissionTo('update-StorageContainer');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StorageContainer $storagecontainer): bool
    {
        return $user->checkPermissionTo('delete-StorageContainer');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StorageContainer $storagecontainer): bool
    {
        return $user->checkPermissionTo('restore-StorageContainer');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StorageContainer $storagecontainer): bool
    {
        return $user->checkPermissionTo('force-delete-StorageContainer');
    }
    
    /**
     * Determine whether the user can permanently delete the model via bulk action.
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-StorageContainer');
    }

    /**
     * Determine whether the user can delete the model via bulk action.
     */
    public function bulkDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-delete-StorageContainer');
    }

    /**
     * Determine whether the user can restore the model via bulk action.
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-StorageContainer');
    }
}
