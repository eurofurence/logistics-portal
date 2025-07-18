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
        return $user->checkPermissionTo('view-any-Order') || $user->hasAnyDepartmentRoleWithPermissionTo('view-any-Order');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        if (empty($order->department->id)) {
            return false;
        }

        if (empty($order->event->id)) {
            return false;
        }

        // Check whether the user is allowed to view the order
        $canViewOrder = ($user->hasDepartmentRoleWithPermissionTo('view-Order', $order->department->id) ||
            $user->checkPermissionTo('can-see-all-orders'));

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

        // Number of departments where the user has the permission to create orders
        $department_counter = $user->getDepartmentsWithPermission_Count('create-Order');

        // Number of open order events
        $event_counter = OrderEvent::where('locked', false)
            ->where(function ($query) {
                $query->whereNull('order_deadline')
                    ->orWhere('order_deadline', '>', now());
            })
            ->count();

        // Checking the conditions for creating a order
        if (($event_counter > 0 || $user->checkPermissionTo('can-always-order')) &&
            ($department_counter > 0 || $user->checkPermissionTo('can-create-orders-for-other-departments'))
        ) {
            $result = true;
        }

        // Return of the final result
        return $result;
    }


    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): bool
    {
        // Initialize the result to false
        $result = false;

        // Check whether the order can be processed
        if (($order->event->locked == false &&
                $order->event->order_deadline < now() &&
                $order->status == 'open' &&
                $order->status != 'locked') &&
            $order->status != 'awaiting_approval' ||
            $user->checkPermissionTo('can-always-edit-orders')
        ) {
            $result = true;
        }

        // Check whether the user has the needed permission inside a department or has access to all departments
        $canAccessDepartment = $user->hasDepartmentRoleWithPermissionTo('update-Order', $order->department->id) ||
            $user->checkPermissionTo('can-edit-all-orders');

        return $canAccessDepartment && $result;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): bool
    {
        // Initialize the result to false
        $result = false;

        // Check whether the order can be deleted
        if ((($order->status == 'open') || ($order->status == 'awaiting_approval')) || $user->checkPermissionTo('can-always-delete-orders')) {
            $result = true;
        }

        // Check whether the user has the needed permission inside a department or has access to all departments
        $canAccessDepartment = $user->hasDepartmentRoleWithPermissionTo('delete-Order', $order->department->id) ||
            $user->checkPermissionTo('can-delete-orders-for-other-departments');

        return $canAccessDepartment && $result;
    }


    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): bool
    {
        return $user->checkPermissionTo('restore-Order') || $user->hasAnyDepartmentRoleWithPermissionTo('restore-Order');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-Order');
    }

    /**
     * Determine whether the user can permanently delete the model. (Many models at once)
     */
    public function bulkForceDelete(User $user): bool
    {
       return $user->checkPermissionTo('bulk-force-delete-Order');
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user, Order $order): bool
    {
        $result = false;

        if (!empty($order->department)) {
            $user->hasDepartmentRoleWithPermissionTo('bulk-delete-Order', $order->department->id);
        }

        return $result || $user->checkPermissionTo('bulk-delete-Order');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user, Order $order): bool
    {
        $result = false;

        if (!empty($order->department)) {
            $user->hasDepartmentRoleWithPermissionTo('bulk-restore-Order', $order->department->id);
        }

        return $result || $user->checkPermissionTo('bulk-restore-Order');
    }
}
