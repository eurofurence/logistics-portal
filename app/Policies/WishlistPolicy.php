<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wishlist;

class WishlistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage-all-Wishlist') || ($user->checkPermissionTo('view-any-Wishlist') && $user->checkPermissionTo('view-Wishlist'));
    }

    public function view(User $user, Wishlist $wishlist): bool
    {
        return $this->viewAny($user) && Wishlist::query()->accessibleTo($user)->whereKey($wishlist->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('manage-all-Wishlist') || ($this->viewAny($user) && $user->checkPermissionTo('create-Wishlist'));
    }

    public function update(User $user, Wishlist $wishlist): bool
    {
        return $user->can('manage-all-Wishlist') || ($this->viewAny($user) && $wishlist->user_id === $user->id && $user->checkPermissionTo('update-Wishlist'));
    }

    public function delete(User $user, Wishlist $wishlist): bool
    {
        return $user->can('manage-all-Wishlist') || ($this->viewAny($user) && $wishlist->user_id === $user->id && $user->checkPermissionTo('delete-Wishlist'));
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('manage-all-Wishlist') || ($this->viewAny($user) && $user->checkPermissionTo('delete-Wishlist'));
    }

    public function manageItems(User $user, Wishlist $wishlist): bool
    {
        return $user->can('manage-all-Wishlist') || ($this->viewAny($user) && $user->checkPermissionTo('update-Wishlist')
            && Wishlist::query()->accessibleTo($user, true)->whereKey($wishlist->id)->exists());
    }

    public function share(User $user, Wishlist $wishlist): bool
    {
        return $this->update($user, $wishlist);
    }

    public function transferOwnership(User $user, Wishlist $wishlist): bool
    {
        return $user->can('manage-all-Wishlist') || ($this->viewAny($user)
            && $wishlist->user_id === $user->id && $user->checkPermissionTo('transfer-ownership-Wishlist'));
    }
}
