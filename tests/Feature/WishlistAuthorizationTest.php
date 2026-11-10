<?php

use App\Filament\App\Resources\OrderArticles\Pages\ViewOrderArticle;
use App\Filament\App\Resources\Wishlists\Pages\CreateWishlist;
use App\Filament\App\Resources\Wishlists\Pages\ListWishlists;
use App\Models\OrderArticle;
use App\Models\Permission;
use App\Models\User;
use App\Models\Wishlist;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

/** @param list<string> $abilities */
function wishlistUser(array $abilities = ['view-any', 'view', 'create', 'update', 'delete']): User
{
    $user = User::factory()->create();

    foreach ($abilities as $ability) {
        $user->givePermissionTo(Permission::findOrCreate($ability.'-Wishlist', 'web'));
    }

    return $user;
}

test('requires each wishlist permission for its corresponding ability', function (string $ability, string $permission) {
    $user = wishlistUser(array_values(array_diff(['view-any', 'view', 'create', 'update', 'delete'], [$permission])));
    $wishlist = Wishlist::factory()->create(['user_id' => $user->id, 'name' => 'My wishlist']);

    expect(Gate::forUser($user)->allows($ability, in_array($ability, ['viewAny', 'create']) ? Wishlist::class : $wishlist))->toBeFalse();
})->with([
    ['viewAny', 'view-any'],
    ['viewAny', 'view'],
    ['create', 'create'],
    ['update', 'update'],
    ['delete', 'delete'],
]);

test('allows an owner with permissions to manage their wishlist', function () {
    $user = wishlistUser();
    $wishlist = Wishlist::factory()->create(['user_id' => $user->id, 'name' => 'My wishlist']);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect(Gate::forUser($user)->allows($ability, $wishlist))->toBeTrue();
    }
});

test('rejects access to another users wishlist even with management permissions', function () {
    $user = wishlistUser();
    $wishlist = Wishlist::factory()->create(['user_id' => User::factory()->create()->id, 'name' => 'Private wishlist']);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect(Gate::forUser($user)->allows($ability, $wishlist))->toBeFalse();
    }
});

test('allows shared users to view but not change or delete a wishlist', function () {
    $user = wishlistUser();
    $wishlist = Wishlist::factory()->create(['user_id' => User::factory()->create()->id, 'name' => 'Shared wishlist']);
    $wishlist->sharedUsers()->attach($user);

    expect(Gate::forUser($user)->allows('view', $wishlist))->toBeTrue();
    expect(Gate::forUser($user)->allows('update', $wishlist))->toBeFalse();
    expect(Gate::forUser($user)->allows('delete', $wishlist))->toBeFalse();
});

test('forbids opening the wishlist overview without permissions', function () {
    $this->actingAs(wishlistUser([]));

    Livewire::test(ListWishlists::class)->assertForbidden();
});

test('hides the add to wishlist action without wishlist permissions', function () {
    $user = wishlistUser([]);
    $user->givePermissionTo(Permission::findOrCreate('view-OrderArticle', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('view-any-OrderArticle', 'web'));
    $this->actingAs($user);
    $article = OrderArticle::factory()->create();

    Livewire::test(ViewOrderArticle::class, ['record' => $article->id])
        ->assertActionHidden('add_to_wishlist');
});

test('assigns new wishlists to the authenticated user despite a forged owner', function () {
    $user = wishlistUser();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(CreateWishlist::class)
        ->fillForm(['name' => 'My wishlist', 'user_id' => $otherUser->id])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('wishlists', ['name' => 'My wishlist', 'user_id' => $user->id]);
});
