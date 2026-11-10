<?php

use App\Filament\App\Resources\Wishlists\Pages\ListWishlists;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\WishlistShared;
use App\Services\WishlistManager;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function sharingNotificationUser(bool $withWishlistRights = true): User
{
    $user = User::factory()->create();
    foreach ($withWishlistRights ? ['view-any', 'view', 'create', 'update'] : [] as $ability) {
        $user->givePermissionTo(Permission::findOrCreate($ability.'-Wishlist', 'web'));
    }

    return $user;
}

function sharingNotificationMembership(User $user, Department $department, bool $canOrder): void
{
    $role = Role::factory()->create();
    if ($canOrder) {
        $role->givePermissionTo(Permission::findOrCreate('can-place-order', 'web'));
    }
    $user->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $role->id]);
}

test('notifies a newly added person through the panel and email', function () {
    Notification::fake([WishlistShared::class]);
    $owner = sharingNotificationUser();
    $recipient = sharingNotificationUser(false);
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Event']);

    app(WishlistManager::class)->share($owner, $wishlist, ['users' => [['id' => $recipient->id, 'access' => 'read']]]);

    Notification::assertSentTo($recipient, WishlistShared::class, fn (WishlistShared $notification, array $channels): bool => $channels === ['database', 'mail'] && $notification->wishlistId === $wishlist->id && $notification->afterCommit);
    Notification::assertCount(1);
});

test('notifies department members only when they can access lists and order for that department', function (bool $hasWishlistRights, bool $canOrder, bool $expected) {
    Notification::fake([WishlistShared::class]);
    $owner = sharingNotificationUser();
    $this->actingAs($owner);
    $recipient = sharingNotificationUser($hasWishlistRights);
    $department = Department::factory()->create();
    sharingNotificationMembership($recipient, $department, $canOrder);
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Department supplies']);

    app(WishlistManager::class)->share($owner, $wishlist, ['departments' => [['id' => $department->id, 'access' => 'read']]]);

    if ($expected) {
        Notification::assertSentTo($recipient, WishlistShared::class, fn (WishlistShared $notification, array $channels): bool => $channels === ['database'] && ! $notification->personal);
    } else {
        Notification::assertNothingSent();
    }
})->with([
    'both permissions' => [true, true, true],
    'no wishlist access' => [false, true, false],
    'no ordering permission' => [true, false, false],
    'neither permission' => [false, false, false],
]);

test('does not use ordering permissions from an unrelated department', function () {
    Notification::fake([WishlistShared::class]);
    $owner = sharingNotificationUser();
    $this->actingAs($owner);
    $recipient = sharingNotificationUser();
    $sharedDepartment = Department::factory()->create();
    sharingNotificationMembership($recipient, $sharedDepartment, false);
    sharingNotificationMembership($recipient, Department::factory()->create(), true);
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Restricted']);

    app(WishlistManager::class)->share($owner, $wishlist, ['departments' => [['id' => $sharedDepartment->id, 'access' => 'read']]]);

    Notification::assertNothingSent();
});

test('requires actual ordering permission when cross department ordering is enabled', function (bool $canOrder) {
    Notification::fake([WishlistShared::class]);
    $owner = sharingNotificationUser();
    $this->actingAs($owner);
    $recipient = sharingNotificationUser();
    $recipient->givePermissionTo(Permission::findOrCreate('can-create-orders-for-other-departments', 'web'));
    $sharedDepartment = Department::factory()->create();
    sharingNotificationMembership($recipient, $sharedDepartment, false);
    sharingNotificationMembership($recipient, Department::factory()->create(), $canOrder);
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Shared']);

    app(WishlistManager::class)->share($owner, $wishlist, ['departments' => [['id' => $sharedDepartment->id, 'access' => 'read']]]);

    if ($canOrder) {
        Notification::assertSentTo($recipient, WishlistShared::class, fn (WishlistShared $notification, array $channels): bool => $channels === ['database']);
    } else {
        Notification::assertNothingSent();
    }
})->with([true, false]);

test('combines overlapping direct and department grants into one notification', function () {
    Notification::fake([WishlistShared::class]);
    $owner = sharingNotificationUser();
    $this->actingAs($owner);
    $recipient = sharingNotificationUser();
    $first = Department::factory()->create();
    $second = Department::factory()->create();
    sharingNotificationMembership($recipient, $first, true);
    sharingNotificationMembership($recipient, $second, true);
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Shared']);

    app(WishlistManager::class)->share($owner, $wishlist, [
        'users' => [['id' => $recipient->id, 'access' => 'edit']],
        'departments' => [['id' => $first->id, 'access' => 'read'], ['id' => $second->id, 'access' => 'edit']],
    ]);

    Notification::assertSentToTimes($recipient, WishlistShared::class, 1);
    Notification::assertSentTo($recipient, WishlistShared::class, fn (WishlistShared $notification, array $channels): bool => $channels === ['database', 'mail']);
});

test('does not resend notifications when unchanged grants or access levels are saved', function () {
    Notification::fake([WishlistShared::class]);
    $owner = sharingNotificationUser();
    $recipient = sharingNotificationUser();
    $manager = app(WishlistManager::class);
    $wishlist = $manager->create($owner, ['name' => 'Shared']);
    $read = ['users' => [['id' => $recipient->id, 'access' => 'read']]];
    $manager->share($owner, $wishlist, $read);
    $manager->share($owner, $wishlist, $read);
    $manager->share($owner, $wishlist, ['users' => [['id' => $recipient->id, 'access' => 'edit']]]);

    Notification::assertSentToTimes($recipient, WishlistShared::class, 1);
});

test('skips queued delivery when the personal grant was revoked', function () {
    $owner = sharingNotificationUser();
    $recipient = sharingNotificationUser();
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Shared']);
    $wishlist->sharedUsers()->attach($recipient, ['access' => 'read']);
    $notification = new WishlistShared($wishlist->id, $wishlist->name, $owner->name, true);

    expect($notification->shouldSend($recipient, 'mail'))->toBeTrue();
    $wishlist->sharedUsers()->detach($recipient);
    expect($notification->shouldSend($recipient, 'mail'))->toBeFalse();
});

test('rechecks department membership before delivering a panel notification', function () {
    $owner = sharingNotificationUser();
    $this->actingAs($owner);
    $recipient = sharingNotificationUser();
    $department = Department::factory()->create();
    sharingNotificationMembership($recipient, $department, true);
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Shared']);
    $wishlist->sharedDepartments()->attach($department, ['access' => 'read']);
    $notification = new WishlistShared($wishlist->id, $wishlist->name, $owner->name, false, [$department->id]);

    expect($notification->shouldSend($recipient, 'database'))->toBeTrue();
    $recipient->departmentMemberships()->delete();
    expect($notification->shouldSend($recipient, 'database'))->toBeFalse();
});

test('stores a Filament compatible database notification and renders the email safely', function () {
    $owner = sharingNotificationUser();
    $recipient = sharingNotificationUser();
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => '<script>alert(1)</script>']);
    $wishlist->sharedUsers()->attach($recipient, ['access' => 'read']);
    $notification = new WishlistShared($wishlist->id, $wishlist->name, $owner->name, true);
    $recipient->notifyNow($notification, ['database']);

    $data = $recipient->notifications()->sole()->data;
    expect($data['format'])->toBe('filament');
    expect($data['body'])->toContain('&lt;script&gt;')->not->toContain('<script>');
    expect($data['actions'][0]['url'])->toBe(route('filament.app.resources.wishlists.index', ['list' => $wishlist->id]));
    expect((string) $notification->toMail($recipient)->render())->toContain('&lt;script&gt;')->not->toContain('<script>alert(1)</script>');
    expect($notification->viaConnections())->toBe(['database' => 'sync']);
});

test('does not deliver share notifications when the surrounding transaction rolls back', function () {
    $owner = sharingNotificationUser();
    $recipient = sharingNotificationUser();
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Not committed']);

    expect(fn () => DB::transaction(function () use ($owner, $recipient, $wishlist): void {
        app(WishlistManager::class)->share($owner, $wishlist, ['users' => [['id' => $recipient->id, 'access' => 'read']]]);
        throw new RuntimeException('Roll back the grant');
    }))->toThrow(RuntimeException::class);

    $this->assertDatabaseCount('notifications', 0);
    expect($wishlist->sharedUsers()->count())->toBe(0);
});

test('makes department panel notifications available only after the share commits', function () {
    $owner = sharingNotificationUser();
    $this->actingAs($owner);
    $recipient = sharingNotificationUser();
    $department = Department::factory()->create();
    sharingNotificationMembership($recipient, $department, true);
    $wishlist = app(WishlistManager::class)->create($owner, ['name' => 'Committed share']);

    DB::transaction(function () use ($owner, $department, $wishlist): void {
        app(WishlistManager::class)->share($owner, $wishlist, ['departments' => [['id' => $department->id, 'access' => 'read']]]);
        $this->assertDatabaseCount('notifications', 0);
    });

    expect($recipient->notifications()->sole()->data['title'])->toBe(__('wishlists.shared_notification_title'));
});

test('uses the translated wishlist title for the page heading', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    app()->setLocale('de');
    $this->actingAs(sharingNotificationUser());

    Livewire::test(ListWishlists::class)->assertSee('Merklisten')->assertDontSee('List Wishlists');
});
