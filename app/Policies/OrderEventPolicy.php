<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\OrderEvent;
use App\Models\User;

class OrderEventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-OrderEvent');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OrderEvent $orderevent): bool
    {
        return $user->checkPermissionTo('view-OrderEvent');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-OrderEvent');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OrderEvent $orderevent): bool
    {
        return $user->checkPermissionTo('update-OrderEvent');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OrderEvent $orderevent): bool
    {
        return $user->checkPermissionTo('delete-OrderEvent');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OrderEvent $orderevent): bool
    {
        return $user->checkPermissionTo('restore-OrderEvent');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OrderEvent $orderevent): bool
    {
        return $user->checkPermissionTo('force-delete-OrderEvent');
    }

    /**
     * Determine whether the user can permanently delete the model. (Many models at once)
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-OrderEvent');
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-delete-OrderEvent');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-OrderEvent');
    }
}
