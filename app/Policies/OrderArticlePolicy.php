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
        return $user->checkPermissionTo('view-any-OrderArticle');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OrderArticle $orderarticle): bool
    {
        return $user->checkPermissionTo('view-OrderArticle');
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

    public function order(User $user, OrderArticle $orderarticle): bool
    {
        $event_counter = OrderEvent::where('locked', false)
            ->where(function ($query) {
                $query->whereNull('order_deadline')
                    ->orWhere('order_deadline', '>', now());
            })
            ->count();

        $department_counter = $user->departments()->count();

        $over_deadline = true;

        if (empty($orderarticle->deadline)) {
            $over_deadline = false;
        } else {
            if (now()->lt($orderarticle->deadline)) {
                $over_deadline = false;
            }
        }

        return (((($event_counter > 0) && !$orderarticle->locked && !$over_deadline) || $user->can('can-always-order')) && (($department_counter > 0) || $user->can('access-all-departments')))  &&  $user->can('can-place-order');
    }

    /**
     * Determine whether the user can change the deadline of the model. (Many models at once)
     */
    public function bulkEditDeadline(User $user)
    {
        return $user->can('can-bulk-change-order-article-deadlines');
    }
}
