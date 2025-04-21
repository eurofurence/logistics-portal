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
        // Check whether the user is allowed to view the order
        $canViewOrder = ($user->hasDepartmentRoleWithPermissionTo('view-Order', $order->department->id) ||
            $user->checkPermissionTo('can-see-all-departments'));

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
        if ((($order->status == 'open') && ($order->status != 'awaiting_approval')) || $user->checkPermissionTo('can-always-delete-orders')) {
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
        return $user->hasAnyDepartmentRoleWithPermissionTo('restore-Order');
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
    public function bulkDelete(User $user): bool
    {
        return $user->hasAnyDepartmentRoleWithPermissionTo('bulk-delete-Order') || $user->can('bulk-delete-Order');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user): bool
    {
        return $user->hasAnyDepartmentRoleWithPermissionTo('bulk-restore-Order')  || $user->can('bulk-restore-Order');
    }

    public function declineOrder(User $user, Order $order)
    {
        // Initialize the result to false
        $result = false;

        // Check if the order can be declined based on its event status and status
        $canDeclineOrder = !$order->event->locked &&
            $order->event->order_deadline < now() &&
            $order->status == 'awaiting_approval';

        // Check if the user has permission to always decline orders
        $hasAlwaysDeclinePermission = $user->checkPermissionTo('can-always-decline-orders');

        // Set result to true if the order can be declined or the user has always decline permission
        if ($canDeclineOrder || $hasAlwaysDeclinePermission) {
            $result = true;
        }

        $hasRequiredPermission = $user->hasDepartmentRoleWithPermissionTo('can-decline-orders', $order->department->id);

        // Check if the user has permission to decline orders for other departments
        $canDeclineForOtherDepartments = $user->checkPermissionTo('can-decline-orders-for-other-departments');

        // Return true if the user is in the department and has the role or can decline orders for other departments, and the result is true
        return ($hasRequiredPermission || $canDeclineForOtherDepartments) && $result;
    }

    public function approveOrder(User $user, Order $order)
    {
        // Initialize the result to false
        $result = false;

        // Check if the order can be approved based on its event status and status
        $canApproveOrder = !$order->event->locked &&
            $order->event->order_deadline < now() &&
            $order->status == 'awaiting_approval';

        // Check if the user has permission to always approve orders
        $hasAlwaysApprovePermission = $user->checkPermissionTo('can-always-approve-orders');

        // Set result to true if the order can be approved or the user has always approve permission
        if ($canApproveOrder || $hasAlwaysApprovePermission) {
            $result = true;
        }

        $hasRequiredPermission = $user->hasDepartmentRoleWithPermissionTo('can-approve-orders', $order->department->id);

        // Check if the user has permission to approve orders for other departments
        $canApproveForOtherDepartments = $user->checkPermissionTo('can-approve-orders-for-other-departments');

        // Return true if the user is in the department and has the role or can approve orders for other departments, and the result is true
        return ($hasRequiredPermission || $canApproveForOtherDepartments) && $result;
    }
}
