<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\TestModel;
use App\Models\User;

class TestModelPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-TestModel');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TestModel $testmodel): bool
    {
        return $user->checkPermissionTo('view-TestModel');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-TestModel');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TestModel $testmodel): bool
    {
        return $user->checkPermissionTo('update-TestModel');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TestModel $testmodel): bool
    {
        return $user->checkPermissionTo('delete-TestModel');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TestModel $testmodel): bool
    {
        return $user->checkPermissionTo('restore-TestModel');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TestModel $testmodel): bool
    {
        return $user->checkPermissionTo('force-delete-TestModel');
    }
}
