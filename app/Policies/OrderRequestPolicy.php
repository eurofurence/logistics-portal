<?php

namespace App\Policies;

use App\Models\User;
use App\Models\OrderEvent;
use App\Models\OrderRequest;

class OrderRequestPolicy
{

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-OrderRequest') || $user->hasAnyDepartmentRoleWithPermissionTo('view-any-OrderRequest');
    }


    public function view(User $user, OrderRequest $orderRequest): bool
    {
        if ($user->checkPermissionTo('can-see-all-orderRequests')) {
            return true;
        }

        $hasRequiredPermission = $user->hasDepartmentRoleWithPermissionTo('view-OrderRequest', $orderRequest->department_id);

        return $hasRequiredPermission;
    }


    public function create(User $user): bool
    {
        // Initialize the result to false
        $result = false;

        // Count the amount of departments where the user can create request
        $department_counter = $user->getDepartmentsWithPermission_Count('create-OrderRequest');

        // Count the number of open order events that are not locked and either have no deadline or a deadline in the future
        $event_counter = OrderEvent::where('locked', false)
            ->where(function ($query) {
                $query->whereNull('order_deadline')
                    ->orWhere('order_deadline', '>', now());
            })
            ->count();

        // Check if the user is allowed to create order requests
        $canCreateOrderRequests = $event_counter > 0 || $user->checkPermissionTo('can-always-create-orderRequests');

        // Check if the user has access to at least one department or has permissions to create order requests for other departments
        $hasDepartmentAccess = $department_counter > 0 || $user->checkPermissionTo('can-create-orderRequests-for-other-departments');

        // If both conditions are met, set the result to true
        if ($canCreateOrderRequests && $hasDepartmentAccess) {
            $result = true;
        }

        return $result;
    }


    public function update(User $user, OrderRequest $orderRequest): bool
    {
        // Initialize the result to false
        $result = false;

        // Check if the order request can be updated based on its event status
        $canUpdateOrderRequest = !$orderRequest->event->locked &&
            $orderRequest->event->order_deadline < now() &&
            $orderRequest->status == 0;

        // Check if the user has permission to always edit order requests
        $hasAlwaysEditPermission = $user->checkPermissionTo('can-always-edit-orderRequests');

        $hasRequiredPermission = $user->hasDepartmentRoleWithPermissionTo('update-OrderRequest', $orderRequest->department_id);

        // Set result to true if the order request can be updated or the user has always edit permission
        if ($canUpdateOrderRequest || $hasAlwaysEditPermission) {
            $result = true;
        }

        // Check if the user has permission to edit all order requests
        $canEditAllOrderRequests = $user->checkPermissionTo('can-edit-all-orderRequests');

        // Return true if the user has the required role or can edit all order requests, and the result is true
        return ($hasRequiredPermission || $canEditAllOrderRequests) && $result;
    }


    public function delete(User $user, OrderRequest $orderRequest): bool
    {
        // Initialize the result to false
        $result = false;

        // Check if the order request can be deleted based on its event status
        $canDeleteOrderRequest = !$orderRequest->event->locked &&
            $orderRequest->event->order_deadline < now() &&
            $orderRequest->status == 0;

        // Check if the user has permission to always delete order requests
        $hasAlwaysDeletePermission = $user->checkPermissionTo('can-always-delete-orderRequests');

        // Set result to true if the order request can be deleted or the user has always delete permission
        if ($canDeleteOrderRequest || $hasAlwaysDeletePermission) {
            $result = true;
        }

        $hasRequiredPermission = $user->hasDepartmentRoleWithPermissionTo('delete-OrderRequest', $orderRequest->department_id);

        // Check if the user has permission to delete for other departments
        $canDeleteForOtherDepartments = $user->checkPermissionTo('can-delete-orderRequests-for-other-departments');

        // Return true if the user is in the department and has the role or can delete for other departments, and the result is true
        return ($hasRequiredPermission || $canDeleteForOtherDepartments) && $result;
    }

    public function restore(User $user): bool
    {
        return $user->hasAnyDepartmentRoleWithPermissionTo('restore-OrderRequest');
    }


    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-OrderRequest');
    }

    /**
     * Determine whether the user can permanently delete the model. (Many models at once)
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-OrderRequest');
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user): bool
    {
        return $user->hasAnyDepartmentRoleWithPermissionTo('bulk-delete-OrderRequest');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user): bool
    {
        return $user->hasAnyDepartmentRoleWithPermissionTo('bulk-restore-OrderRequest');
    }
}
