<?php

namespace App\Policies;

use App\Models\User;
use App\Models\OrderEvent;
use App\Models\OrderArticle;

class OrderArticlePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-OrderArticle') || $user->hasAnyDepartmentRoleWithPermissionTo('view-any-OrderArticle');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OrderArticle $orderarticle): bool
    {
        return $user->checkPermissionTo('view-OrderArticle') || $user->hasAnyDepartmentRoleWithPermissionTo('view-OrderArticle');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-OrderArticle');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OrderArticle $orderarticle): bool
    {
        return $user->checkPermissionTo('update-OrderArticle');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OrderArticle $orderarticle): bool
    {
        return $user->checkPermissionTo('delete-OrderArticle');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OrderArticle $orderarticle): bool
    {
        return $user->checkPermissionTo('restore-OrderArticle');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OrderArticle $orderarticle): bool
    {
        return $user->checkPermissionTo('force-delete-OrderArticle');
    }

    /**
     * Determine whether the user can permanently delete the model. (Many models at once)
     */
    public function bulkForceDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-force-delete-OrderArticle');
    }

    /**
     * Determine whether the user can delete the model. (Many models at once)
     */
    public function bulkDelete(User $user): bool
    {
        return $user->checkPermissionTo('bulk-delete-OrderArticle');
    }

    /**
     * Determine whether the user can restore the model. (Many models at once)
     */
    public function bulkRestore(User $user): bool
    {
        return $user->checkPermissionTo('bulk-restore-OrderArticle');
    }

    /**
     * Determine if the user can place an order based on specific conditions.
     *
     * This method checks several conditions to determine if the user is allowed to place an order:
     * 1. There must be at least one unlocked order event that either has no deadline or has a future deadline.
     * 2. The order article must not be locked and must not be past its deadline.
     * 3. The user must belong to at least one department or have the permission to choose all departments.
     * 4. The user must have the permission to place an order or place an order with approval.
     *
     * @param User $user The user attempting to place the order.
     * @param OrderArticle $orderarticle The order article being considered.
     * @return bool True if the user can place the order, false otherwise.
     */
    public function order(User $user, OrderArticle $orderarticle): bool
    {
        // Count the number of unlocked order events that are either without a deadline or have a future deadline.
        $event_counter = OrderEvent::where('locked', false)
            ->where(function ($query) {
                $query->whereNull('order_deadline')
                    ->orWhere('order_deadline', '>', now());
            })
            ->count();

        // Count the number of departments the user belongs to.
        $department_counter = $user->departments()->count();

        // Determine if the order article is over its deadline.
        $over_deadline = !empty($orderarticle->deadline) && now()->gte($orderarticle->deadline);

        // Check if the user can place the order based on the conditions.
        return (
            (($event_counter > 0) && !$orderarticle->locked && !$over_deadline) || $user->can('can-always-order')
        ) && (
            ($department_counter > 0) || $user->can('can-choose-all-departments')
        ) && $user->hasAnyDepartmentRoleWithPermissionTo('can-place-order');
    }


    /**
     * Determine whether the user can change the deadline of the model. (Many models at once)
     */
    public function bulkEditDeadline(User $user)
    {
        return $user->can('can-bulk-change-order-article-deadlines');
    }
}
