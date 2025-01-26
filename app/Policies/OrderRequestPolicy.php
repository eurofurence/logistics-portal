<?php

namespace App\Policies;

use App\Models\User;
use App\Models\OrderEvent;
use App\Models\OrderRequest;
use Illuminate\Auth\Access\Response;

class OrderRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-OrderRequest');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OrderRequest $orderrequest): bool
    {
        if (!$user->checkPermissionTo('view-OrderRequest')) {
            return false;
        }

        return ($user->departments->contains('id', $orderrequest->department_id) || $user->checkPermissionTo('access-all-departments'));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $result = false;
        $department_counter = $user->departments()->count();
        $event_counter = OrderEvent::where('locked', false)
            ->where(function ($query) {
                $query->whereNull('order_deadline')
                    ->orWhere('order_deadline', '>', now());
            })
            ->count();

        if ((($event_counter > 0) || $user->checkPermissionTo('can-always-create-orderRequests')) && (($department_counter > 0) || $user->checkPermissionTo('access-all-departments'))) {
            $result = true;
        }

        return $user->checkPermissionTo('create-OrderRequest') && $result;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OrderRequest $orderrequest): bool
    {
        $result = false;

        if (!$user->checkPermissionTo('update-OrderRequest')) {
            return false;
        }

        if (($orderrequest->event->locked == false && ($orderrequest->event->order_deadline < now()) && $orderrequest->status == 0 || $user->checkPermissionTo('can-always-edit-orderRequests'))) {
            $result = true;
        }

        return ($user->departments->contains('id', $orderrequest->department_id) || $user->checkPermissionTo('access-all-departments')) && $result;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OrderRequest $orderrequest): bool
    {
        $result = false;

        if (!$user->checkPermissionTo('delete-OrderRequest')) {
            return false;
        }

        if (($orderrequest->event->locked == false && ($orderrequest->event->order_deadline < now()) && $orderrequest->status == 0 || $user->checkPermissionTo('can-always-delete-orderRequests'))) {
            $result = true;
        }

        return ($user->departments->contains('id', $orderrequest->department_id) || $user->checkPermissionTo('access-all-departments')) && $result;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OrderRequest $orderrequest): bool
    {
        $result = false;

        if (!$user->checkPermissionTo('restore-OrderRequest')) {
            return false;
        }

        if ($orderrequest->status == 0 || $user->checkPermissionTo('can-always-restore-orderRequests')) {
            $result = true;
        }

        return $user->departments->contains('id', $orderrequest->department_id) || $user->checkPermissionTo('access-all-departments') && $result;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OrderRequest $orderrequest): bool
    {
        return $user->checkPermissionTo('force-delete-OrderRequest');
    }

    /**
     * Determine whether the user can permanently delete the model. (Many models at once)
     */
    public function bulkForceDelete(User $user, OrderRequest $orderrequest): bool
    {
        if (!$user->checkPermissionTo('bulk-force-delete-OrderRequest')) {
            return false;
        }

        return $user->departments->contains('id', $orderrequest->department_id) || $user->checkPermissionTo('access-all-departments');
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user, OrderRequest $orderrequest): bool
    {
        if (!$user->checkPermissionTo('bulk-delete-OrderRequest')) {
            return false;
        }

        return $user->departments->contains('id', $orderrequest->department_id) || $user->checkPermissionTo('access-all-departments');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user, OrderRequest $orderrequest): bool
    {
        if (!$user->checkPermissionTo('bulk-restore-OrderRequest')) {
            return false;
        }

        return $user->departments->contains('id', $orderrequest->department_id) || $user->checkPermissionTo('access-all-departments');
    }
}
