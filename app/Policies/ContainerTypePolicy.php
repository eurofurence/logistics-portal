<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\ContainerType;
use App\Models\User;

class ContainerTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-ContainerType');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ContainerType $containertype): bool
    {
        return $user->checkPermissionTo('view-ContainerType');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-ContainerType');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ContainerType $containertype): bool
    {
        return $user->checkPermissionTo('update-ContainerType');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ContainerType $containertype): bool
    {
        return $user->checkPermissionTo('delete-ContainerType');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ContainerType $containertype): bool
    {
        return $user->checkPermissionTo('restore-ContainerType');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ContainerType $containertype): bool
    {
        return $user->checkPermissionTo('force-delete-ContainerType');
    }

    /**
     * Determine whether the user can permanently delete the model. (Many models at once)
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-ContainerType');
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-delete-ContainerType');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-ContainerType');
    }
}
