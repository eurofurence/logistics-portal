<?php

namespace App\Services;

use App\Models\Department;
use App\Models\OrderArticle;
use App\Models\User;
use App\Models\Wishlist;
use App\Notifications\WishlistShared;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WishlistManager
{
    public function lists(User $user, bool $editable = false): Builder
    {
        Gate::forUser($user)->authorize('viewAny', Wishlist::class);

        $query = Wishlist::query()->accessibleTo($user, $editable)->with('user')->withCount('items');
        if ($editable && ! $user->can('manage-all-Wishlist') && ! $user->checkPermissionTo('update-Wishlist')) {
            $query->whereRaw('1 = 0');
        }

        return $query->orderBy('name')->orderBy('id');
    }

    public function target(User $user, bool $create = false): ?Wishlist
    {
        $lists = $this->lists($user, true);
        $preferredId = User::query()->whereKey($user->id)->value('last_wishlist_id');
        $target = $preferredId ? (clone $lists)->whereKey($preferredId)->first() : null;
        $target ??= $lists->first();

        if (! $target && $create) {
            Gate::forUser($user)->authorize('create', Wishlist::class);
            abort_unless($user->can('manage-all-Wishlist') || $user->checkPermissionTo('update-Wishlist'), 403);

            $target = DB::transaction(function () use ($user): Wishlist {
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
                $existing = $this->lists($lockedUser, true)->first();

                return $existing ?? $this->create($lockedUser, ['name' => __('wishlists.default_name')]);
            });
        }

        return $target;
    }

    public function select(User $user, int $wishlistId): Wishlist
    {
        $wishlist = Wishlist::findOrFail($wishlistId);
        Gate::forUser($user)->authorize('manageItems', $wishlist);
        $user->forceFill(['last_wishlist_id' => $wishlist->id])->save();

        return $wishlist;
    }

    /** @param array{name: string, description?: ?string} $data */
    public function create(User $user, array $data): Wishlist
    {
        Gate::forUser($user)->authorize('create', Wishlist::class);
        $data = Validator::make($data, ['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000']])->validate();

        return Wishlist::create([...$data, 'user_id' => $user->id, 'is_public' => false]);
    }

    public function add(User $user, Wishlist $wishlist, int $articleId): void
    {
        Gate::forUser($user)->authorize('manageItems', $wishlist);
        $article = OrderArticle::findOrFail($articleId);
        Gate::forUser($user)->authorize('view', $article);
        $wishlist->items()->firstOrCreate(['order_article_id' => $article->id]);
        $this->select($user, $wishlist->id);
    }

    /** @param list<int> $articleIds */
    public function remove(User $user, Wishlist $wishlist, array $articleIds): string
    {
        Gate::forUser($user)->authorize('manageItems', $wishlist);
        $removedIds = DB::transaction(function () use ($wishlist, $articleIds): array {
            $ids = $wishlist->items()->whereIn('order_article_id', $articleIds)->lockForUpdate()->pluck('order_article_id')->all();
            $wishlist->items()->whereIn('order_article_id', $ids)->delete();

            return $ids;
        });
        $token = Str::random(64);
        Cache::put('wishlist-undo:'.$token, ['user_id' => $user->id, 'wishlist_id' => $wishlist->id, 'article_ids' => $removedIds, 'expires_at' => now()->addSeconds(10)->timestamp], 10);

        return $token;
    }

    public function undo(User $user, string $token): void
    {
        $payload = Cache::get('wishlist-undo:'.$token);
        if (! $payload || $payload['expires_at'] <= now()->timestamp) {
            throw ValidationException::withMessages(['undo' => __('wishlists.undo_expired')]);
        }
        abort_unless($payload['user_id'] === $user->id, 403);
        $wishlist = Wishlist::findOrFail($payload['wishlist_id']);
        Gate::forUser($user)->authorize('manageItems', $wishlist);
        DB::transaction(function () use ($user, $wishlist, $payload): void {
            foreach (OrderArticle::whereIn('id', $payload['article_ids'])->get() as $article) {
                Gate::forUser($user)->authorize('view', $article);
                $wishlist->items()->firstOrCreate(['order_article_id' => $article->id]);
            }
        });
        Cache::forget('wishlist-undo:'.$token);
    }

    /** @param array{users?: list<array{id: int, access: string}>, departments?: list<array{id: int, access: string}>} $data */
    public function share(User $user, Wishlist $wishlist, array $data): void
    {
        DB::transaction(function () use ($user, $wishlist, $data): void {
            $wishlist = Wishlist::query()->whereKey($wishlist->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('share', $wishlist);
            $data = Validator::make($data, [
                'users' => ['array'], 'users.*.id' => ['required', 'integer', 'distinct', Rule::notIn([$user->id, $wishlist->user_id]), Rule::exists(User::class, 'id')->whereNull('deleted_at')],
                'users.*.access' => ['required', Rule::in(['read', 'edit'])],
                'departments' => ['array'], 'departments.*.id' => ['required', 'integer', 'distinct', Rule::exists(Department::class, 'id')->whereNull('deleted_at')],
                'departments.*.access' => ['required', Rule::in(['read', 'edit'])],
            ], ['users.*.id.not_in' => __('wishlists.self_share_forbidden')])->validate();
            $userChanges = $wishlist->sharedUsers()->sync(collect($data['users'] ?? [])->mapWithKeys(fn (array $grant): array => [$grant['id'] => ['access' => $grant['access']]])->all());
            $departmentChanges = $wishlist->sharedDepartments()->sync(collect($data['departments'] ?? [])->mapWithKeys(fn (array $grant): array => [$grant['id'] => ['access' => $grant['access']]])->all());
            $this->notifyNewShares($user, $wishlist, $userChanges['attached'], $departmentChanges['attached']);
        });
    }

    public function transferOwnership(User $user, Wishlist $wishlist, int $newOwnerId): Wishlist
    {
        return DB::transaction(function () use ($user, $wishlist, $newOwnerId): Wishlist {
            $wishlist = Wishlist::query()->lockForUpdate()->findOrFail($wishlist->id);
            Gate::forUser($user)->authorize('transferOwnership', $wishlist);
            Validator::make(['new_owner_id' => $newOwnerId], [
                'new_owner_id' => ['required', 'integer', Rule::notIn([$wishlist->user_id]), Rule::exists(User::class, 'id')->whereNull('deleted_at')],
            ])->validate();

            $wishlist->sharedUsers()->detach([$wishlist->user_id, $newOwnerId]);
            $wishlist->user_id = $newOwnerId;
            $wishlist->save();

            return $wishlist;
        });
    }

    /**
     * @param  list<int>  $userIds
     * @param  list<int>  $departmentIds
     */
    protected function notifyNewShares(User $actor, Wishlist $wishlist, array $userIds, array $departmentIds): void
    {
        if ($userIds === [] && $departmentIds === []) {
            return;
        }

        User::query()->whereNotIn('users.id', [$actor->id, $wishlist->user_id])
            ->where(function (Builder $query) use ($userIds, $departmentIds): void {
                $query->whereIn('users.id', $userIds)
                    ->orWhereHas('departments', fn (Builder $query): Builder => $query->whereIn('departments.id', $departmentIds));
            })->with(['roles.permissions', 'permissions'])->chunkById(100, function (Collection $recipients) use ($actor, $wishlist, $userIds, $departmentIds): void {
                foreach ($recipients as $recipient) {
                    $notification = new WishlistShared($wishlist->id, $wishlist->name, $actor->name, in_array($recipient->id, $userIds, true), $departmentIds);
                    if ($notification->shouldSend($recipient, 'database')) {
                        $recipient->notify($notification->locale(app()->getLocale()));
                    }
                }
            });
    }
}
