<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wishlist;

class WishlistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-Wishlist') && $user->checkPermissionTo('view-Wishlist');
    }

    public function view(User $user, Wishlist $wishlist): bool
    {
        return $this->viewAny($user) && ($wishlist->user_id === $user->id || $wishlist->sharedUsers()->whereKey($user->id)->exists());
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->checkPermissionTo('create-Wishlist');
    }

    public function update(User $user, Wishlist $wishlist): bool
    {
        return $this->viewAny($user) && $wishlist->user_id === $user->id && $user->checkPermissionTo('update-Wishlist');
    }

    public function delete(User $user, Wishlist $wishlist): bool
    {
        return $this->viewAny($user) && $wishlist->user_id === $user->id && $user->checkPermissionTo('delete-Wishlist');
    }

    public function deleteAny(User $user): bool
    {
        return $this->viewAny($user) && $user->checkPermissionTo('delete-Wishlist');
    }
}
