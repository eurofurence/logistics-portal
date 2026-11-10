<?php

use App\Filament\Admin\Pages\ManageGeneral;
use App\Filament\App\Resources\Items\ItemResource;
use App\Filament\Pages\Dashboard;
use App\Models\Bill;
use App\Models\Department;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\HomepageOverview;
use App\Settings\GeneralSettings;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('homepage limits order counts and previews to readable department records', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $department = Department::factory()->create();
    $role = Role::factory()->create();
    $role->givePermissionTo(Permission::findOrCreate('view-any-Order', 'web'), Permission::findOrCreate('view-Order', 'web'));
    $user->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $role->id]);
    $event = OrderEvent::factory()->create();
    $visible = Order::factory()->createQuietly(['name' => 'Readable department order', 'department_id' => $department->id, 'order_event_id' => $event->id]);
    Order::factory()->createQuietly(['name' => 'Locked department order', 'department_id' => $department->id, 'order_event_id' => $event->id, 'status' => 'locked']);
    Order::factory()->createQuietly(['name' => 'Foreign department order', 'order_event_id' => $event->id]);

    $section = app(HomepageOverview::class)->sections('overview')['orders'];
    expect($section['count'])->toBe(1);
    expect(array_column($section['records'], 'title'))->toBe([$visible->name]);
    Livewire::test(Dashboard::class)
        ->assertSee($visible->name)
        ->assertDontSee('Locked department order')
        ->assertDontSee('Foreign department order');

    $user->givePermissionTo(Permission::findOrCreate('can-see-all-orders', 'web'), Permission::findOrCreate('can-always-see-order', 'web'));
    $section = app(HomepageOverview::class)->sections('overview')['orders'];
    expect($section['count'])->toBe(3);
    expect(array_column($section['records'], 'title'))->toContain('Locked department order', 'Foreign department order');
});

test('homepage shows open bills only from permitted departments', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $department = Department::factory()->create();
    $role = Role::factory()->create();
    $role->givePermissionTo(Permission::findOrCreate('view-any-Bill', 'web'), Permission::findOrCreate('view-Bill', 'web'));
    $user->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $role->id]);
    $event = OrderEvent::factory()->create();
    Bill::factory()->createQuietly(['title' => 'Visible open invoice', 'department_id' => $department->id, 'order_event_id' => $event->id]);
    Bill::factory()->createQuietly(['title' => 'Closed invoice', 'department_id' => $department->id, 'order_event_id' => $event->id, 'status' => 'paid']);
    Bill::factory()->createQuietly(['title' => 'Foreign invoice', 'order_event_id' => $event->id]);

    $section = app(HomepageOverview::class)->sections('overview')['bills'];
    expect($section['count'])->toBe(1);
    expect(array_column($section['records'], 'title'))->toBe(['Visible open invoice']);
    Livewire::test(Dashboard::class)->assertSee('Visible open invoice')->assertDontSee('Closed invoice')->assertDontSee('Foreign invoice');
});

test('homepage includes shared wishlists and limits previews to the latest five', function () {
    $this->freezeTime();
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('view-any-Wishlist', 'web'), Permission::findOrCreate('view-Wishlist', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Wishlist::factory()->count(5)->create(['name' => 'Older wishlist', 'user_id' => $user->id, 'updated_at' => now()->subDay()]);
    $shared = Wishlist::factory()->create(['name' => 'Shared planning list', 'user_id' => User::factory()]);
    $shared->sharedUsers()->attach($user, ['access' => 'read']);
    Wishlist::factory()->create(['name' => 'Private foreign list', 'user_id' => User::factory()]);

    $section = app(HomepageOverview::class)->sections('overview')['wishlists'];
    expect($section['count'])->toBe(6);
    expect($section['records'])->toHaveCount(5);
    expect($section['records'][0]['title'])->toBe('Shared planning list');
    expect($section['records'][0]['url'])->toContain('list='.$shared->id);
    Livewire::test(Dashboard::class)->assertSee('Shared planning list')->assertDontSee('Private foreign list');
});

test('homepage displays neutral content when no resources are accessible', function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(Dashboard::class)
        ->assertSee(__('homepage.no_sections'))
        ->assertDontSeeHtml('role="tablist"')
        ->assertDontSee(__('homepage.workflow'));
});

test('disabled areas and guide-only mode never query order data', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('view-any-Order', 'web'), Permission::findOrCreate('can-see-all-orders', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    GeneralSettings::fake(['homepage_overview_enabled' => false]);
    DB::enableQueryLog();

    Livewire::test(Dashboard::class)
        ->assertSee(__('homepage.orders_description'))
        ->assertDontSeeHtml('role="tablist"');

    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();
    expect($queries->filter(fn (string $sql): bool => (bool) preg_match('/\bfrom\s+["`]?orders["`]?(?:\s|$)/i', $sql)))->toBeEmpty();

    GeneralSettings::fake(['homepage_overview_enabled' => true, 'homepage_overview_orders' => false, 'homepage_guide_orders' => false]);
    Livewire::test(Dashboard::class)->assertSee(__('homepage.no_sections'))->assertDontSee(__('homepage.orders_description'));
});

test('homepage escapes custom text and uses translated defaults for empty fields', function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel(Filament::getPanel('app'));
    GeneralSettings::fake(['homepage_title' => '<script>alert(1)</script>', 'homepage_intro' => null]);

    Livewire::test(Dashboard::class)
        ->assertSeeHtml('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->assertDontSeeHtml('<script>alert(1)</script>')
        ->assertSee(__('homepage.intro'));
});

test('administrators can persist homepage switches and texts', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'), Permission::findOrCreate('access-general-settings', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ManageGeneral::class)
        ->fillForm(['homepage_overview_orders' => false, 'homepage_guide_enabled' => false, 'homepage_title' => 'Hello team', 'homepage_intro' => 'Daily work', 'homepage_orders_description' => 'Order supplies'])
        ->call('save')->assertHasNoFormErrors();

    $settings = app(GeneralSettings::class)->refresh();
    expect($settings->homepage_overview_orders)->toBeFalse();
    expect($settings->homepage_guide_enabled)->toBeFalse();
    expect($settings->homepage_title)->toBe('Hello team');
    Livewire::test(ManageGeneral::class)->assertFormSet(['homepage_title' => 'Hello team', 'homepage_overview_orders' => false]);
});

test('homepage text length is validated before saving', function (string $field, int $maximum) {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'), Permission::findOrCreate('access-general-settings', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $page = Livewire::test(ManageGeneral::class)->fillForm([$field => str_repeat('a', $maximum)])
        ->call('save')->assertHasNoFormErrors();
    $page->fillForm([$field => str_repeat('b', $maximum + 1)])->call('save')->assertHasFormErrors([$field => 'max']);
    expect(app(GeneralSettings::class)->refresh()->{$field})->toBe(str_repeat('a', $maximum));
})->with([
    ['homepage_title', 120], ['homepage_intro', 600],
    ['homepage_orders_description', 400], ['homepage_bills_description', 400],
    ['homepage_wishlists_description', 400], ['homepage_inventory_description', 400],
]);

test('homepage switches cannot be saved without general settings access', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ManageGeneral::class)->assertForbidden();
    expect(app(GeneralSettings::class)->homepage_overview_enabled)->toBeTrue();
});

test('inventory links distinguish browsing and creation permissions from storage permissions', function (bool $canCreate) {
    $user = User::factory()->create();
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $department = Department::factory()->create();
    $role = Role::factory()->create();
    $role->givePermissionTo(Permission::findOrCreate('view-any-Item', 'web'));
    $expectedLinks = [ItemResource::getUrl('index', panel: 'app')];
    if ($canCreate) {
        $role->givePermissionTo(Permission::findOrCreate('create-Item', 'web'));
        $expectedLinks[] = ItemResource::getUrl('create', panel: 'app');
    }
    $user->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $role->id]);

    $section = app(HomepageOverview::class)->sections('guide')['inventory'];
    expect(array_column($section['links'], 'url'))->toBe($expectedLinks);
    Livewire::test(Dashboard::class)->assertSee(__('homepage.inventory_description'));
})->with(['browse only' => false, 'browse and create' => true]);

test('disabling both tabs preserves the welcome without exposing resource data', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('Master', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    GeneralSettings::fake(['homepage_overview_enabled' => false, 'homepage_guide_enabled' => false]);

    Livewire::test(Dashboard::class)
        ->assertSee($user->name)->assertSee(__('homepage.no_sections'))
        ->assertDontSeeHtml('role="tablist"')->assertDontSee(__('homepage.workflow'));
    expect(app(HomepageOverview::class)->sections('overview'))->toBeEmpty();
    expect(app(HomepageOverview::class)->sections('guide'))->toBeEmpty();
});

test('guide descriptions are escaped and section switches apply independently to each tab', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('view-any-Wishlist', 'web'), Permission::findOrCreate('view-Wishlist', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    GeneralSettings::fake([
        'homepage_overview_wishlists' => false,
        'homepage_wishlists_description' => '<img src=x onerror=alert(1)>',
    ]);

    Livewire::test(Dashboard::class)
        ->assertSeeHtml('&lt;img src=x onerror=alert(1)&gt;')
        ->assertDontSeeHtml('<img src=x onerror=alert(1)>')
        ->assertDontSee(__('homepage.wishlists_count'));
    expect(app(HomepageOverview::class)->sections('overview'))->toBeEmpty();
    expect(app(HomepageOverview::class)->sections('guide'))->toHaveKey('wishlists');
});
