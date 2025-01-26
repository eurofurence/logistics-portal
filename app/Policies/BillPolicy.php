<?php

namespace App\Policies;

use App\Models\Bill;
use App\Models\User;
use App\Models\OrderEvent;
use Illuminate\Auth\Access\Response;

class BillPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-Bill');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Bill $bill): bool
    {
        if (!$user->checkPermissionTo('view-Bill')) {
            return false;
        }

        return (($user->departments->contains('id', $bill->department_id) || $user->checkPermissionTo('can-see-all-departments'))  || $user->checkPermissionTo('access-all-departments'));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $result = false;
        $department_counter = $user->departments()->count();
        $event_counter = OrderEvent::all(['id'])->count();

        if (($event_counter > 0) && (($department_counter > 0) || $user->checkPermissionTo('access-all-departments'))) {
            $result = true;
        }

        return $user->checkPermissionTo('create-Bill') && $result;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Bill $bill): bool
    {
        $result = false;

        if (!$user->checkPermissionTo('update-Bill')) {
            return false;
        }

        if ($bill->status == 'open' || $user->checkPermissionTo('can-always-edit-bills')) {
            $result = true;
        }

        return ($user->departments->contains('id', $bill->department_id) || $user->checkPermissionTo('access-all-departments')) && $result;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Bill $bill): bool
    {
        $result = false;

        if (!$user->checkPermissionTo('delete-Bill')) {
            return false;
        }

        if ($bill->status == 'open' || $user->checkPermissionTo('can-always-delete-bills')) {
            $result = true;
        }

        return ($user->departments->contains('id', $bill->department_id) || $user->checkPermissionTo('access-all-departments')) && $result;
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any-Bill');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Bill $bill): bool
    {
        $result = false;

        if (!$user->checkPermissionTo('restore-Bill')) {
            return false;
        }

        return $user->departments->contains('id', $bill->department_id) || $user->checkPermissionTo('access-all-departments') && $result;
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any-Bill');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, Bill $bill): bool
    {
        $result = false;

        if (!$user->checkPermissionTo('replicate-Bill')) {
            return false;
        }

        return $user->departments->contains('id', $bill->department_id) || $user->checkPermissionTo('access-all-departments') && $result;
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder-Bill');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Bill $bill): bool
    {
        if (!$user->checkPermissionTo('force-delete-Bill')) {
            return false;
        }

        return $user->departments->contains('id', $bill->department_id) || $user->checkPermissionTo('access-all-departments');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any-Bill');
    }

    public function bulkForceDelete(User $user, Bill $bill): bool
    {
        if (!$user->checkPermissionTo('bulk-force-delete-Bill')) {
            return false;
        }

        return $user->departments->contains('id', $bill->department_id) || $user->checkPermissionTo('access-all-departments');
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user, Bill $bill): bool
    {
        if (!$user->checkPermissionTo('bulk-delete-Bill')) {
            return false;
        }

        return $user->departments->contains('id', $bill->department_id) || $user->checkPermissionTo('access-all-departments');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user, Bill $bill): bool
    {
        if (!$user->checkPermissionTo('bulk-restore-Bill')) {
            return false;
        }

        return $user->departments->contains('id', $bill->department_id) || $user->checkPermissionTo('access-all-departments');
    }
}
