<?php

use App\Filament\App\Resources\OrderArticles\OrderArticleResource;
use App\Filament\App\Resources\OrderArticles\Pages\ListOrderArticles;
use App\Filament\App\Resources\Wishlists\Pages\ListWishlists;
use App\Models\Department;
use App\Models\OrderArticle;
use App\Models\OrderEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\WishlistManager;
use App\Settings\LoginSettings;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    app(LoginSettings::class)->whitelist_active = false;
});

/** @param list<string> $extraPermissions */
function wishlistIntegrationUser(array $extraPermissions = []): User
{
    $user = User::factory()->create();
    foreach ([...array_map(fn (string $ability): string => $ability.'-Wishlist', ['view-any', 'view', 'create', 'update', 'delete']), 'view-OrderArticle', 'view-any-OrderArticle', ...$extraPermissions] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

test('shares read or article editing access without granting ownership powers', function (string $access, bool $canEdit) {
    $owner = wishlistIntegrationUser();
    $user = wishlistIntegrationUser();
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Event supplies']);
    app(WishlistManager::class)->share($owner, $wishlist, ['users' => [['id' => $user->id, 'access' => $access]]]);

    expect(Gate::forUser($user)->allows('view', $wishlist))->toBeTrue();
    expect(Gate::forUser($user)->allows('manageItems', $wishlist))->toBe($canEdit);
    foreach (['update', 'share', 'delete'] as $ability) {
        expect(Gate::forUser($user)->allows($ability, $wishlist))->toBeFalse();
    }
})->with([['read', false], ['edit', true]]);

test('department access follows current membership and combines with direct grants', function () {
    $owner = wishlistIntegrationUser();
    $this->actingAs($owner);
    $user = wishlistIntegrationUser();
    $department = Department::factory()->create();
    $user->departments()->attach($department);
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Team']);
    app(WishlistManager::class)->share($owner, $wishlist, [
        'users' => [['id' => $user->id, 'access' => 'read']],
        'departments' => [['id' => $department->id, 'access' => 'edit']],
    ]);

    expect(Gate::forUser($user)->allows('manageItems', $wishlist))->toBeTrue();
    $user->departments()->detach($department);
    expect(Gate::forUser($user)->allows('manageItems', $wishlist))->toBeFalse();
    expect(Gate::forUser($user)->allows('view', $wishlist))->toBeTrue();
    app(WishlistManager::class)->share($owner, $wishlist, []);
    expect(Gate::forUser($user)->allows('view', $wishlist))->toBeFalse();
});

test('global managers can manage lists and grants belonging to other users', function () {
    $owner = wishlistIntegrationUser();
    $admin = User::factory()->create();
    $admin->givePermissionTo(Permission::findOrCreate('manage-all-Wishlist', 'web'));
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Private']);

    foreach (['view', 'manageItems', 'update', 'share', 'delete'] as $ability) {
        expect(Gate::forUser($admin)->allows($ability, $wishlist))->toBeTrue();
    }
    expect(app(WishlistManager::class)->lists($admin)->pluck('id')->all())->toBe([$wishlist->id]);
});

test('remembers the quick target and skips a revoked target', function () {
    $user = wishlistIntegrationUser();
    $other = wishlistIntegrationUser();
    $manager = app(WishlistManager::class);
    $own = $manager->create($user, ['name' => 'Own']);
    $shared = $manager->create($other, ['name' => 'Shared']);
    $manager->share($other, $shared, ['users' => [['id' => $user->id, 'access' => 'edit']]]);
    $manager->select($user, $shared->id);

    expect($manager->target($user->fresh())->id)->toBe($shared->id);
    $manager->share($other, $shared, []);
    expect($manager->target($user->fresh())->id)->toBe($own->id);
});

test('creates a personal target on first save and prevents duplicate entries', function () {
    $user = wishlistIntegrationUser();
    $this->actingAs($user);
    $article = OrderArticle::factory()->create();
    $manager = app(WishlistManager::class);
    $wishlist = $manager->target($user, true);
    $manager->add($user, $wishlist, $article->id);
    $manager->add($user, $wishlist, $article->id);

    expect($wishlist->user_id)->toBe($user->id);
    expect($wishlist->items()->pluck('order_article_id')->all())->toBe([$article->id]);
    expect($user->fresh()->last_wishlist_id)->toBe($wishlist->id);
});

test('refuses writes to a foreign list through the service', function () {
    $owner = wishlistIntegrationUser();
    $this->actingAs($owner);
    $user = wishlistIntegrationUser();
    $article = OrderArticle::factory()->create();
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Private']);

    expect(fn () => app(WishlistManager::class)->add($user, $wishlist, $article->id))->toThrow(AuthorizationException::class);
    expect($wishlist->items()->count())->toBe(0);
});

test('requires article viewing permission when saving an article', function () {
    $user = wishlistIntegrationUser();
    $this->actingAs($user);
    $user->revokePermissionTo('view-OrderArticle');
    $wishlist = app(WishlistManager::class)->create($user, ['name' => 'Mine']);
    $article = OrderArticle::factory()->create();

    expect(fn () => app(WishlistManager::class)->add($user, $wishlist, $article->id))->toThrow(AuthorizationException::class);
    expect($wishlist->items()->count())->toBe(0);
});

test('removes articles and restores them once within the undo window', function () {
    $user = wishlistIntegrationUser();
    $this->actingAs($user);
    $manager = app(WishlistManager::class);
    $wishlist = $manager->create($user, ['name' => 'Mine']);
    $article = OrderArticle::factory()->create();
    $manager->add($user, $wishlist, $article->id);
    $token = $manager->remove($user, $wishlist, [$article->id]);

    expect($wishlist->items()->count())->toBe(0);
    $manager->undo($user, $token);
    expect($wishlist->items()->pluck('order_article_id')->all())->toBe([$article->id]);
    expect(fn () => $manager->undo($user, $token))->toThrow(ValidationException::class);
});

test('rejects expired undo operations', function () {
    $user = wishlistIntegrationUser();
    $this->actingAs($user);
    $manager = app(WishlistManager::class);
    $wishlist = $manager->create($user, ['name' => 'Mine']);
    $article = OrderArticle::factory()->create();
    $manager->add($user, $wishlist, $article->id);
    $token = $manager->remove($user, $wishlist, [$article->id]);
    $this->travel(11)->seconds();

    expect(fn () => $manager->undo($user, $token))->toThrow(ValidationException::class);
    expect($wishlist->items()->count())->toBe(0);
});

test('rechecks editing access before undo and binds the token to its actor', function () {
    $owner = wishlistIntegrationUser();
    $this->actingAs($owner);
    $user = wishlistIntegrationUser();
    $manager = app(WishlistManager::class);
    $wishlist = $manager->create($owner, ['name' => 'Shared']);
    $article = OrderArticle::factory()->create();
    $manager->add($owner, $wishlist, $article->id);
    $manager->share($owner, $wishlist, ['users' => [['id' => $user->id, 'access' => 'edit']]]);
    $token = $manager->remove($user, $wishlist, [$article->id]);

    expect(fn () => $manager->undo($owner, $token))->toThrow(HttpException::class);
    $manager->share($owner, $wishlist, []);
    expect(fn () => $manager->undo($user, $token))->toThrow(AuthorizationException::class);
    expect($wishlist->items()->count())->toBe(0);
});

test('renders the custom overview and removes multiple articles through Livewire', function () {
    $user = wishlistIntegrationUser();
    $this->actingAs($user);
    $manager = app(WishlistManager::class);
    $wishlist = $manager->create($user, ['name' => 'Weekend']);
    $articles = OrderArticle::factory()->count(2)->create();
    foreach ($articles as $article) {
        $manager->add($user, $wishlist, $article->id);
    }

    Livewire::test(ListWishlists::class, ['selectedWishlistId' => $wishlist->id])
        ->assertSee('Weekend')->assertSee($articles[0]->name)
        ->set('selectedArticles', $articles->modelKeys())->call('removeSelected')->assertHasNoErrors();

    expect($wishlist->items()->count())->toBe(0);
});

test('rejects a forged selection in the overview', function () {
    $user = wishlistIntegrationUser();
    $owner = wishlistIntegrationUser();
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Private']);
    $this->actingAs($user);

    Livewire::test(ListWishlists::class)->call('chooseWishlist', $wishlist->id)->assertForbidden();
});

test('saves and removes an article directly from its directory card', function () {
    $user = wishlistIntegrationUser();
    $this->actingAs($user);
    $article = OrderArticle::factory()->create();

    $component = Livewire::test(ListOrderArticles::class)
        ->callAction(TestAction::make('add_to_wishlist')->table($article))->assertHasNoErrors()
        ->assertActionHasIcon(TestAction::make('add_to_wishlist')->table($article), 'heroicon-s-bookmark');
    $wishlist = app(WishlistManager::class)->target($user->fresh());
    expect($wishlist->items()->pluck('order_article_id')->all())->toBe([$article->id]);

    $component->callAction(TestAction::make('add_to_wishlist')->table($article))->assertHasNoErrors()
        ->assertActionHasIcon(TestAction::make('add_to_wishlist')->table($article), 'heroicon-o-bookmark');
    expect($wishlist->items()->count())->toBe(0);
});

test('saves user and department access levels through the sharing dialog', function () {
    $owner = wishlistIntegrationUser();
    $this->actingAs($owner);
    $user = wishlistIntegrationUser();
    $department = Department::factory()->create();
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Team shopping']);

    Livewire::test(ListWishlists::class, ['selectedWishlistId' => $wishlist->id])
        ->callAction('shareWishlist', data: [
            'users' => [['id' => $user->id, 'access' => 'read']],
            'departments' => [['id' => $department->id, 'access' => 'edit']],
        ])->assertHasNoActionErrors();

    expect($wishlist->sharedUsers()->first()->pivot->access)->toBe('read');
    expect($wishlist->sharedDepartments()->first()->pivot->access)->toBe('edit');
});

test('renders a read only shared list without management actions', function () {
    $owner = wishlistIntegrationUser();
    $user = wishlistIntegrationUser();
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Read only']);
    app(WishlistManager::class)->share($owner, $wishlist, ['users' => [['id' => $user->id, 'access' => 'read']]]);
    $this->actingAs($user);

    Livewire::test(ListWishlists::class, ['selectedWishlistId' => $wishlist->id])
        ->assertSee('Read only')->assertActionHidden('renameWishlist')
        ->assertActionHidden('shareWishlist')->assertActionHidden('deleteWishlist');
});

test('searches only the selected list and escapes its text', function () {
    $user = wishlistIntegrationUser();
    $this->actingAs($user);
    $manager = app(WishlistManager::class);
    $wishlist = $manager->create($user, ['name' => '<script>alert(1)</script>']);
    $article = OrderArticle::factory()->create(['name' => 'Camping chair', 'article_number' => 'CHAIR-42']);
    $other = OrderArticle::factory()->create(['name' => 'Different article']);
    $manager->add($user, $wishlist, $article->id);
    $manager->add($user, $wishlist, $other->id);

    Livewire::test(ListWishlists::class, ['selectedWishlistId' => $wishlist->id])
        ->set('search', 'CHAIR-42')->assertSee('Camping chair')->assertDontSee('Different article')
        ->assertSeeHtml('&lt;script&gt;alert(1)&lt;/script&gt;')->assertDontSeeHtml('<script>alert(1)</script>');
});

test('switches quick targets and renders the slide over preview', function () {
    $user = wishlistIntegrationUser();
    $this->actingAs($user);
    $wishlist = app(WishlistManager::class)->create($user, ['name' => 'Packing list']);

    Livewire::test(ListOrderArticles::class)
        ->callAction('wishlistTarget', data: ['wishlist_id' => $wishlist->id])->assertHasNoActionErrors()
        ->mountAction('wishlists')->assertSee('Packing list');

    expect($user->fresh()->last_wishlist_id)->toBe($wishlist->id);
});

test('switches the selected list when changing between own and shared lists', function () {
    $user = wishlistIntegrationUser();
    $owner = wishlistIntegrationUser();
    $manager = app(WishlistManager::class);
    $mine = $manager->create($user, ['name' => 'My list']);
    $shared = $manager->create($owner, ['name' => 'Shared list']);
    $manager->share($owner, $shared, ['users' => [['id' => $user->id, 'access' => 'read']]]);
    $this->actingAs($user);

    Livewire::test(ListWishlists::class, ['selectedWishlistId' => $mine->id])
        ->set('listScope', 'shared')->assertSet('selectedWishlistId', $shared->id)
        ->set('listScope', 'mine')->assertSet('selectedWishlistId', $mine->id);
});

test('hides quick target controls when the user can only read lists', function () {
    $user = wishlistIntegrationUser();
    $user->revokePermissionTo('update-Wishlist');
    $this->actingAs($user);

    Livewire::test(ListOrderArticles::class)
        ->assertActionVisible('wishlists')->assertActionHidden('wishlistTarget');
});

test('opens a newly created list in the own lists section', function () {
    $user = wishlistIntegrationUser();
    $this->actingAs($user);

    Livewire::test(ListWishlists::class, ['listScope' => 'shared'])
        ->callAction('newWishlist', data: ['name' => 'New personal list'])
        ->assertHasNoActionErrors()->assertSet('listScope', 'mine')->assertSee('New personal list');

    expect(Wishlist::sole()->user_id)->toBe($user->id);
});

test('allows removal of unavailable articles from the overview', function () {
    $user = wishlistIntegrationUser();
    $this->actingAs($user);
    $manager = app(WishlistManager::class);
    $wishlist = $manager->create($user, ['name' => 'My list']);
    $article = OrderArticle::factory()->create(['name' => 'Discontinued lantern', 'article_number' => 'OLD-42']);
    $manager->add($user, $wishlist, $article->id);
    $article->delete();

    Livewire::test(ListWishlists::class, ['selectedWishlistId' => $wishlist->id])
        ->assertSee('Discontinued lantern')->assertSee('OLD-42')
        ->assertSee(__('wishlists.no_longer_available'))
        ->assertDontSeeHtml('href="'.OrderArticleResource::getUrl('view', ['record' => $article]).'"')
        ->set('search', 'OLD-42')->assertSee('Discontinued lantern')
        ->call('removeWishlistArticle', $wishlist->id, $article->id)->assertHasNoErrors();

    expect($wishlist->items()->count())->toBe(0);
});

test('marks deleted articles in quick access without exposing their detail link', function () {
    $user = wishlistIntegrationUser();
    $this->actingAs($user);
    $manager = app(WishlistManager::class);
    $wishlist = $manager->create($user, ['name' => 'Quick list']);
    $article = OrderArticle::factory()->create(['name' => 'Retired quick access article']);
    $manager->add($user, $wishlist, $article->id);
    $article->delete();

    $component = Livewire::test(ListOrderArticles::class)->mountAction('wishlists')->assertActionMounted('wishlists');
    $preview = $component->instance()->getMountedAction()->getModalContent()
        ->with('errors', new ViewErrorBag)->render();

    expect($preview)->toContain('Retired quick access article', e(__('wishlists.no_longer_available')))
        ->not->toContain('href="'.OrderArticleResource::getUrl('view', ['record' => $article]).'"');

    $this->assertDatabaseHas('wishlist_items', ['wishlist_id' => $wishlist->id, 'order_article_id' => $article->id]);
});

test('does not readd or restore a deleted article when undoing its removal', function () {
    $user = wishlistIntegrationUser();
    $this->actingAs($user);
    $manager = app(WishlistManager::class);
    $wishlist = $manager->create($user, ['name' => 'My list']);
    $article = OrderArticle::factory()->create();
    $manager->add($user, $wishlist, $article->id);
    $article->delete();

    $token = $manager->remove($user, $wishlist, [$article->id]);
    $manager->undo($user, $token);

    $this->assertDatabaseMissing('wishlist_items', ['wishlist_id' => $wishlist->id, 'order_article_id' => $article->id]);
    $this->assertSoftDeleted($article);
    expect(fn () => $manager->add($user, $wishlist, $article->id))->toThrow(ModelNotFoundException::class);
});

test('orders an article from a read only shared wishlist without removing it', function () {
    $user = wishlistIntegrationUser();
    $owner = wishlistIntegrationUser();
    $this->actingAs($user);
    $department = Department::factory()->create();
    $role = Role::factory()->create();
    $role->givePermissionTo(Permission::findOrCreate('can-place-order', 'web'));
    $user->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $role->id]);
    $event = OrderEvent::factory()->create();
    $article = OrderArticle::factory()->create(['locked' => false, 'deadline' => null]);
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Shared order list']);
    $wishlist->sharedUsers()->attach($user, ['access' => 'read']);
    $wishlist->items()->create(['order_article_id' => $article->id]);

    Livewire::test(ListWishlists::class, ['selectedWishlistId' => $wishlist->id])
        ->assertActionVisible(TestAction::make('orderWishlistArticle')->arguments(['article_id' => $article->id]))
        ->callAction(TestAction::make('orderWishlistArticle')->arguments(['article_id' => $article->id]), data: [
            'quantity' => 3, 'department' => $department->id, 'order_event' => $event->id, 'comment' => 'For the team',
        ])->assertHasNoActionErrors();

    $this->assertDatabaseHas('orders', ['order_article_id' => $article->id, 'amount' => 3, 'department_id' => $department->id, 'order_event_id' => $event->id, 'comment' => 'For the team']);
    $this->assertDatabaseHas('wishlist_items', ['wishlist_id' => $wishlist->id, 'order_article_id' => $article->id]);
});

test('hides and rejects ordering unavailable or unauthorized wishlist articles', function (string $restriction) {
    $user = wishlistIntegrationUser();
    $this->actingAs($user);
    $department = Department::factory()->create();
    $role = Role::factory()->create();
    if ($restriction !== 'permission') {
        $role->givePermissionTo(Permission::findOrCreate('can-place-order', 'web'));
    }
    $user->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $role->id]);
    $event = OrderEvent::factory()->create();
    $article = OrderArticle::factory()->create(['locked' => $restriction === 'locked', 'deadline' => null]);
    $wishlist = app(WishlistManager::class)->create($user, ['name' => 'Order list']);
    if ($restriction !== 'outside-list') {
        $wishlist->items()->create(['order_article_id' => $article->id]);
    }
    if ($restriction === 'deleted') {
        $article->delete();
    }
    if ($restriction === 'invisible') {
        $user->revokePermissionTo('view-OrderArticle');
    }

    Livewire::test(ListWishlists::class, ['selectedWishlistId' => $wishlist->id])
        ->assertActionHidden(TestAction::make('orderWishlistArticle')->arguments(['article_id' => $article->id]))
        ->call('mountAction', 'orderWishlistArticle', ['article_id' => $article->id])
        ->assertActionNotMounted();

    $this->assertDatabaseCount('orders', 0);
})->with(['permission', 'locked', 'deleted', 'invisible', 'outside-list']);

test('paginates wishlist articles and resets the page when searching', function () {
    $user = wishlistIntegrationUser();
    $this->actingAs($user);
    $wishlist = app(WishlistManager::class)->create($user, ['name' => 'Large list']);
    $articles = OrderArticle::factory()->count(19)->sequence(fn ($sequence): array => ['name' => 'Paged article '.$sequence->index])->create();
    foreach ($articles as $article) {
        $wishlist->items()->create(['order_article_id' => $article->id]);
    }

    Livewire::test(ListWishlists::class, ['selectedWishlistId' => $wishlist->id])
        ->assertSee('Paged article 18')->assertDontSee('Paged article 0')
        ->call('setPage', 2)->assertSee('Paged article 0')->assertDontSee('Paged article 18')
        ->set('search', 'Paged article 18')->assertSee('Paged article 18');
});
