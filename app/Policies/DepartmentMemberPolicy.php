<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\DepartmentMember;
use App\Models\User;

class DepartmentMemberPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-DepartmentMember');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DepartmentMember $departmentmember): bool
    {
        return $user->checkPermissionTo('view-DepartmentMember');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-DepartmentMember');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DepartmentMember $departmentmember): bool
    {
        return $user->checkPermissionTo('update-DepartmentMember');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DepartmentMember $departmentmember): bool
    {
        return $user->checkPermissionTo('delete-DepartmentMember');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DepartmentMember $departmentmember): bool
    {
        return $user->checkPermissionTo('restore-DepartmentMember');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DepartmentMember $departmentmember): bool
    {
        return $user->checkPermissionTo('force-delete-DepartmentMember');
    }

    /**
     * Determine whether the user can permanently delete the model. (Many models at once)
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-DepartmentMember');
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-delete-DepartmentMember');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-DepartmentMember');
    }
}
