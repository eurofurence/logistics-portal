<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderEvent;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-Order');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        // Check whether the user has authorization to view orders
        if (!$user->checkPermissionTo('view-Order')) {
            return false;
        }

        // Check whether the user is allowed to view the order
        $canViewOrder = ($user->departments->contains('id', $order->department_id) ||
            $user->checkPermissionTo('can-see-all-departments') ||
            $user->checkPermissionTo('can-choose-all-departments'));

        $orderNotLockedOrPermission = ($order->status != 'locked') ||
            $user->checkPermissionTo('can-always-see-order');

        return $canViewOrder && $orderNotLockedOrPermission;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Initialization of the result
        $result = false;

        // Number of departments of the user
        $department_counter = $user->departments()->count();

        // Number of open order events
        $event_counter = OrderEvent::where('locked', false)
            ->where(function ($query) {
                $query->whereNull('order_deadline')
                    ->orWhere('order_deadline', '>', now());
            })
            ->count();

        // Checking the conditions for creating a purchase order
        if (($event_counter > 0 || $user->checkPermissionTo('can-always-order')) &&
            ($department_counter > 0 || $user->checkPermissionTo('can-choose-all-departments'))
        ) {
            $result = true;
        }

        // Return of the final result
        return $user->checkPermissionTo('create-Order') && $result;
    }


    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): bool
    {
        $result = false;

        // Check whether the user has authorization to update orders
        if (!$user->checkPermissionTo('update-Order')) {
            return false;
        }

        // Check whether the order can be processed
        if (($order->event->locked == false &&
                $order->event->order_deadline < now() &&
                $order->status == 'open' &&
                $order->status != 'locked') ||
            $user->checkPermissionTo('can-always-edit-orders')
        ) {
            $result = true;
        }

        // Check whether the user belongs to the department of the order or has access to all departments
        $canAccessDepartment = $user->departments->contains('id', $order->department_id) ||
            $user->checkPermissionTo('can-choose-all-departments');

        return $canAccessDepartment && $result;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): bool
    {
        $result = false;

        if (!$user->checkPermissionTo('delete-Order')) {
            return false;
        }

        if ($order->status == 'open' || $user->checkPermissionTo('can-always-delete-orders')) {
            $result = true;
        }

        return ($user->departments->contains('id', $order->department_id) || $user->checkPermissionTo('can-choose-all-departments')) && $result;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        $result = false;

        if (!$user->checkPermissionTo('restore-Order')) {
            return false;
        }

        if ($order->status == 'open' || $user->checkPermissionTo('can-always-restore-orders')) {
            $result = true;
        }

        return $user->departments->contains('id', $order->department_id) || $user->checkPermissionTo('can-choose-all-departments') && $result;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        if (!$user->checkPermissionTo('force-delete-Order')) {
            return false;
        }

        return $user->departments->contains('id', $order->department_id) || $user->checkPermissionTo('can-choose-all-departments');
    }

    /**
     * Determine whether the user can permanently delete the model. (Many models at once)
     */
    public function bulkForceDelete(User $user, Order $order): bool
    {
        if (!$user->checkPermissionTo('bulk-force-delete-Order')) {
            return false;
        }

        return $user->departments->contains('id', $order->department_id) || $user->checkPermissionTo('can-choose-all-departments');
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user, Order $order): bool
    {
        if (!$user->checkPermissionTo('bulk-delete-Order')) {
            return false;
        }

        return $user->departments->contains('id', $order->department_id) || $user->checkPermissionTo('can-choose-all-departments');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user, Order $order): bool
    {
        if (!$user->checkPermissionTo('bulk-restore-Order')) {
            return false;
        }

        return $user->departments->contains('id', $order->department_id) || $user->checkPermissionTo('can-choose-all-departments');
    }
}
