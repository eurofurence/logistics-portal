<?php

use App\Filament\App\Resources\Wishlists\Pages\ListWishlists;
use App\Models\Department;
use App\Models\Permission;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\WishlistManager;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

function wishlistOwnershipUser(bool $canTransfer = true): User
{
    $user = User::factory()->create();
    foreach (['view-any', 'view', 'create', 'update', 'delete'] as $ability) {
        $user->givePermissionTo(Permission::findOrCreate($ability.'-Wishlist', 'web'));
    }
    if ($canTransfer) {
        $user->givePermissionTo(Permission::findOrCreate('transfer-ownership-Wishlist', 'web'));
    }

    return $user;
}

test('transfers through the panel and removes the former owners private access', function () {
    $owner = wishlistOwnershipUser();
    $recipient = wishlistOwnershipUser();
    $wishlist = Wishlist::factory()->create(['user_id' => $owner->id, 'name' => 'Event supplies']);
    $this->actingAs($owner);

    Livewire::test(ListWishlists::class, ['selectedWishlistId' => $wishlist->id])
        ->callAction('transferOwnership', data: ['new_owner_id' => $recipient->id])
        ->assertHasNoActionErrors()->assertSet('selectedWishlistId', null)->assertSuccessful();

    $this->assertDatabaseHas('wishlists', ['id' => $wishlist->id, 'user_id' => $recipient->id]);
    expect(Gate::forUser($owner)->allows('view', $wishlist->fresh()))->toBeFalse();
    expect(Gate::forUser($recipient)->allows('update', $wishlist->fresh()))->toBeTrue();
    expect($wishlist->sharedUsers()->count())->toBe(0);
});

test('requires the dedicated permission even for the owner', function () {
    $owner = wishlistOwnershipUser(false);
    $recipient = wishlistOwnershipUser();
    $wishlist = Wishlist::factory()->create(['user_id' => $owner->id, 'name' => 'Event supplies']);
    $this->actingAs($owner);

    Livewire::test(ListWishlists::class, ['selectedWishlistId' => $wishlist->id])->assertActionHidden('transferOwnership');
    expect(fn () => app(WishlistManager::class)->transferOwnership($owner, $wishlist, $recipient->id))->toThrow(AuthorizationException::class);
    $this->assertDatabaseHas('wishlists', ['id' => $wishlist->id, 'user_id' => $owner->id]);
});

test('does not let a shared editor transfer another users list', function () {
    $owner = wishlistOwnershipUser();
    $editor = wishlistOwnershipUser();
    $wishlist = Wishlist::factory()->create(['user_id' => $owner->id, 'name' => 'Event supplies']);
    $wishlist->sharedUsers()->attach($editor, ['access' => 'edit']);

    expect(fn () => app(WishlistManager::class)->transferOwnership($editor, $wishlist, $editor->id))->toThrow(AuthorizationException::class);
    $this->assertDatabaseHas('wishlists', ['id' => $wishlist->id, 'user_id' => $owner->id]);
});

test('allows global managers to transfer foreign lists', function () {
    $owner = wishlistOwnershipUser();
    $admin = wishlistOwnershipUser(false);
    $admin->givePermissionTo(Permission::findOrCreate('manage-all-Wishlist', 'web'));
    $wishlist = Wishlist::factory()->create(['user_id' => $owner->id, 'name' => 'Event supplies']);

    app(WishlistManager::class)->transferOwnership($admin, $wishlist, $admin->id);

    $this->assertDatabaseHas('wishlists', ['id' => $wishlist->id, 'user_id' => $admin->id]);
});

test('rejects invalid recipients without changing ownership', function (string $target) {
    $owner = wishlistOwnershipUser();
    $wishlist = Wishlist::factory()->create(['user_id' => $owner->id, 'name' => 'Event supplies']);
    $deleted = User::factory()->create();
    $deleted->delete();
    $recipientId = match ($target) {
        'self' => $owner->id,
        'deleted' => $deleted->id,
        'missing' => $deleted->id + 100,
    };

    expect(fn () => app(WishlistManager::class)->transferOwnership($owner, $wishlist, $recipientId))->toThrow(ValidationException::class);
    $this->assertDatabaseHas('wishlists', ['id' => $wishlist->id, 'user_id' => $owner->id]);
})->with(['self', 'deleted', 'missing']);

test('retains existing department and other personal grants while removing the new owners grant', function () {
    $owner = wishlistOwnershipUser();
    $recipient = wishlistOwnershipUser();
    $other = wishlistOwnershipUser();
    $this->actingAs($owner);
    $department = Department::factory()->create();
    $owner->departments()->attach($department);
    $wishlist = Wishlist::factory()->create(['user_id' => $owner->id, 'name' => 'Event supplies']);
    $wishlist->sharedUsers()->attach([$recipient->id => ['access' => 'edit'], $other->id => ['access' => 'read']]);
    $wishlist->sharedDepartments()->attach($department, ['access' => 'read']);

    Livewire::test(ListWishlists::class, ['selectedWishlistId' => $wishlist->id])
        ->callAction('transferOwnership', data: ['new_owner_id' => $recipient->id])
        ->assertHasNoActionErrors()->assertSet('selectedWishlistId', $wishlist->id)->assertSet('listScope', 'shared');

    expect(Gate::forUser($owner)->allows('view', $wishlist->fresh()))->toBeTrue();
    expect(Gate::forUser($owner)->allows('update', $wishlist->fresh()))->toBeFalse();
    expect($wishlist->sharedUsers()->pluck('users.id')->all())->toBe([$other->id]);
    expect($wishlist->sharedDepartments()->pluck('departments.id')->all())->toBe([$department->id]);
});

test('rechecks ownership for stale transfer and sharing requests', function () {
    $owner = wishlistOwnershipUser();
    $recipient = wishlistOwnershipUser();
    $wishlist = Wishlist::factory()->create(['user_id' => $owner->id, 'name' => 'Event supplies']);
    $manager = app(WishlistManager::class);
    $manager->transferOwnership($owner, $wishlist, $recipient->id);

    expect(fn () => $manager->transferOwnership($owner, $wishlist, $owner->id))->toThrow(AuthorizationException::class);
    expect(fn () => $manager->share($owner, $wishlist, []))->toThrow(AuthorizationException::class);
    $this->assertDatabaseHas('wishlists', ['id' => $wishlist->id, 'user_id' => $recipient->id]);
});

test('rejects personal shares to the actor or owner without changing existing grants', function (string $target) {
    $owner = wishlistOwnershipUser();
    $actor = wishlistOwnershipUser();
    $actor->givePermissionTo(Permission::findOrCreate('manage-all-Wishlist', 'web'));
    $existing = wishlistOwnershipUser();
    $wishlist = Wishlist::factory()->create(['user_id' => $owner->id, 'name' => 'Event supplies']);
    $wishlist->sharedUsers()->attach($existing, ['access' => 'read']);
    $targetId = $target === 'actor' ? $actor->id : $owner->id;

    try {
        app(WishlistManager::class)->share($actor, $wishlist, ['users' => [['id' => $targetId, 'access' => 'edit']]]);
        $this->fail('A personal self-share was accepted.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['users.0.id'])->toContain(__('wishlists.self_share_forbidden'));
    }

    expect($wishlist->sharedUsers()->pluck('users.id')->all())->toBe([$existing->id]);
})->with(['actor', 'owner']);
