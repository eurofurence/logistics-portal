<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Department;
use App\Models\OrderEvent;
use App\Models\OrderRequest;
use App\Enums\DepartmentRoleEnum;
use Illuminate\Support\Facades\Auth;

class OrderRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * @param User $user The user making the request.
     * @return bool True if the user has permission to view any models, false otherwise.
     *
     * This function checks if the user has the 'can-see-all-orderRequests' permission. If so, it returns true,
     * indicating that the user can view all models. Otherwise, it checks if the user has any of the
     * specified department roles (Requestor, Purchaser, Director) and returns true if they do. If the user
     * does not meet any of these conditions, it returns false.
     */
    public function viewAny(User $user): bool
    {
        if ($user->checkPermissionTo('can-see-all-orderRequests')) {
            return true;
        }

        return $user->hasDepartmentRoles($user->id, null, [DepartmentRoleEnum::REQUESTOR(), DepartmentRoleEnum::PURCHASER(), DepartmentRoleEnum::DIRECTOR()]);
    }


    /**
     * Determines whether the user can view a specific order request.
     *
     * @param User $user The user making the request.
     * @param OrderRequest $orderRequest The order request to be viewed.
     * @return bool True if the user can view the order request, false otherwise.
     *
     * This function checks if the user has the 'can-see-all-orderRequests' permission. If so, it returns true,
     * indicating that the user can view all models. Otherwise, it checks if the user has any of the
     * specified department roles (Requestor, Purchaser, Director) for the department associated with the
     * order request and returns true if they do. If the user does not meet any of these conditions, it
     * returns false.
     */
    public function view(User $user, OrderRequest $orderRequest): bool
    {
        if ($user->checkPermissionTo('can-see-all-orderRequests')) {
            return true;
        }

        // Check if the user has any of the required roles in the department
        $hasRequiredRole = $user->hasDepartmentRoles(
            $user->id,
            $orderRequest->department->id,
            [
                DepartmentRoleEnum::REQUESTOR(),
                DepartmentRoleEnum::PURCHASER(),
                DepartmentRoleEnum::DIRECTOR()
            ]
        );

        // Return true if the user has the required role
        return $hasRequiredRole;
    }


    /**
     * Determines whether the user can create a new order request.
     *
     * @param User $user The user making the request.
     * @return bool True if the user can create an order request, false otherwise.
     *
     * The function checks the following conditions:
     * 1. If there are open order events that are not locked and have no deadline or a deadline in the future,
     *    or if the user has the 'can-always-create-orderRequests' permission, the function returns true.
     * 2. If the user belongs to at least one department or has the 'can-choose-all-departments' permission and
     *    the 'can-create-orderRequests-for-other-departments' permission, the function returns true.
     * 3. In all other cases, the function returns false.
     */
    public function create(User $user): bool
    {
        // Initialize the result to false
        $result = false;

        // Count the number of departments the user belongs to
        $department_counter = $user->departments()->count();

        // Count the number of open order events that are not locked and either have no deadline or a deadline in the future
        $event_counter = OrderEvent::where('locked', false)
            ->where(function ($query) {
                $query->whereNull('order_deadline')
                    ->orWhere('order_deadline', '>', now());
            })
            ->count();

        // Check if the user is allowed to create order requests
        $canCreateOrderRequests = $event_counter > 0 || $user->checkPermissionTo('can-always-create-orderRequests');

        // Check if the user has access to at least one department or has permissions to choose all departments and create order requests for other departments
        $hasDepartmentAccess = $department_counter > 0 ||
            ($user->checkPermissionTo('can-choose-all-departments') &&
                $user->checkPermissionTo('can-create-orderRequests-for-other-departments'));

        // If both conditions are met, set the result to true
        if ($canCreateOrderRequests && $hasDepartmentAccess) {
            $result = true;
        }

        // Return the result
        return $result;
    }


    /**
     * Determines whether the user can update a specific order request.
     *
     * @param User $user The user making the request.
     * @param OrderRequest $orderRequest The order request to be updated.
     * @return bool True if the user can update the order request, false otherwise.
     *
     * The function checks the following conditions:
     * 1. If the order event associated with the order request is not locked and has a deadline in the past and the order request status is 0,
     *    or if the user has the 'can-always-edit-orderRequests' permission, the function returns true.
     * 2. If the user has any of the required roles (Requestor, Director) in the department associated with the order request,
     *    or if the user has the 'can-edit-all-orderRequests' permission, the function returns true.
     * 3. In all other cases, the function returns false.
     */
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

        // Set result to true if the order request can be updated or the user has always edit permission
        if ($canUpdateOrderRequest || $hasAlwaysEditPermission) {
            $result = true;
        }

        // Check if the user has any of the required roles in the department
        $hasRequiredRole = $user->hasDepartmentRoles(
            $user->id,
            $orderRequest->department->id,
            [
                DepartmentRoleEnum::REQUESTOR(),
                DepartmentRoleEnum::DIRECTOR()
            ]
        );

        // Check if the user has permission to edit all order requests
        $canEditAllOrderRequests = $user->checkPermissionTo('can-edit-all-orderRequests');

        // Return true if the user has the required role or can edit all order requests, and the result is true
        return ($hasRequiredRole || $canEditAllOrderRequests) && $result;
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

        // Check if the user belongs to the department associated with the order request and has the required role
        $hasRole = $user->hasDepartmentRoles($user->id, null, [DepartmentRoleEnum::REQUESTOR(), DepartmentRoleEnum::DIRECTOR()]);

        // Check if the user has permission to delete for other departments
        $canDeleteForOtherDepartments = $user->checkPermissionTo('can-delete-orderRequests-for-other-departments');

        // Return true if the user is in the department and has the role or can delete for other departments, and the result is true
        return ($hasRole || $canDeleteForOtherDepartments) && $result;
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

        return $user->departments->contains('id', $orderrequest->department_id) || $user->checkPermissionTo('can-choose-all-departments') && $result;
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
        return $user->checkPermissionTo('bulk-delete-OrderRequest');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-OrderRequest');
    }
}
