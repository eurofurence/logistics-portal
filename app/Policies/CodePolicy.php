<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Code;
use App\Models\User;

class CodePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-Code');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Code $code): bool
    {
        return $user->checkPermissionTo('view-Code');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-Code');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Code $code): bool
    {
        return $user->checkPermissionTo('update-Code');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Code $code): bool
    {
        return $user->checkPermissionTo('delete-Code');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Code $code): bool
    {
        return $user->checkPermissionTo('restore-Code');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Code $code): bool
    {
        return $user->checkPermissionTo('force-delete-Code');
    }

    /**
     * Determine whether the user can permanently delete the model. (Many models at once)
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-Code');
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-delete-Code');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-Code');
    }
}
