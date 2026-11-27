<?php

use App\Filament\App\Resources\Orders\Pages\EditOrder;
use App\Filament\App\Resources\Orders\Pages\ListOrders;
use App\Filament\App\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    config(['cache.default' => 'array']);
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

function supplierDeliveryUser(bool $canChangeStatus = true): User
{
    $user = User::factory()->create();
    foreach (['view-any-Order', 'can-see-all-orders', ...($canChangeStatus ? ['can-change-order-status'] : [])] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

test('records cumulative supplier delivery quantities through the status action', function () {
    $this->actingAs(supplierDeliveryUser());
    $order = Order::factory()->create(['amount' => 10, 'status' => 'ordered']);
    $page = Livewire::test(ListOrders::class);

    $page->callAction(TestAction::make('set_status')->table($order), ['status' => 'partially_delivered', 'delivered_quantity' => 3])
        ->assertHasNoActionErrors();
    expect($order->fresh()->delivered_quantity)->toBe(3);
    expect($order->fresh()->status)->toBe('partially_delivered');
    expect($order->fresh()->supplierDeliveryProgress())->toBe('3 / 10');

    $page->callAction(TestAction::make('set_status')->table($order), ['status' => 'partially_delivered', 'delivered_quantity' => 5])
        ->assertHasNoActionErrors();
    expect($order->fresh()->delivered_quantity)->toBe(5);
    expect($order->fresh()->supplierDeliveryProgress())->toBe('5 / 10');
});

test('requires a valid partial delivery quantity in the status action', function (mixed $quantity) {
    $this->actingAs(supplierDeliveryUser());
    $order = Order::factory()->create(['amount' => 10, 'status' => 'ordered']);

    Livewire::test(ListOrders::class)->callAction(TestAction::make('set_status')->table($order), [
        'status' => 'partially_delivered', 'delivered_quantity' => $quantity,
    ])->assertHasActionErrors(['delivered_quantity']);

    expect($order->fresh()->status)->toBe('ordered');
    expect($order->fresh()->delivered_quantity)->toBeNull();
})->with(['missing' => null, 'zero' => 0, 'negative' => -1, 'fraction' => 2.5, 'full quantity' => 10, 'excess' => 11]);

test('sets the full delivered quantity when marking the order delivered', function () {
    $this->actingAs(supplierDeliveryUser());
    $order = Order::factory()->create(['amount' => 10, 'status' => 'partially_delivered', 'delivered_quantity' => 4]);

    Livewire::test(ListOrders::class)->callAction(TestAction::make('set_status')->table($order), ['status' => 'delivered'])
        ->assertHasNoActionErrors();

    expect($order->fresh()->delivered_quantity)->toBe(10);
    expect($order->fresh()->delivery_date)->not->toBeNull();
});

test('validates partial quantities even when bypassing the form', function (mixed $quantity) {
    $this->actingAs(supplierDeliveryUser());
    $order = Order::factory()->create(['amount' => 10, 'status' => 'ordered']);

    expect(fn () => $order->update(['status' => 'partially_delivered', 'delivered_quantity' => $quantity]))->toThrow(ValidationException::class);
    expect($order->fresh()->status)->toBe('ordered');
})->with(['missing' => null, 'zero' => 0, 'fraction' => 2.5, 'full' => 10, 'excess' => 11]);

test('prevents reducing the order quantity below the already delivered amount', function () {
    $this->actingAs(supplierDeliveryUser());
    $order = Order::factory()->create(['amount' => 10, 'status' => 'partially_delivered', 'delivered_quantity' => 6]);

    expect(fn () => $order->update(['amount' => 5]))->toThrow(ValidationException::class);
    expect($order->fresh()->amount)->toBe(10);
    expect($order->fresh()->delivered_quantity)->toBe(6);
});

test('denies delivery quantity changes without status permission', function () {
    $this->actingAs(supplierDeliveryUser(false));
    $order = Order::factory()->create(['amount' => 10, 'status' => 'ordered']);

    expect(fn () => $order->update(['delivered_quantity' => 3]))->toThrow(HttpException::class);
    expect($order->fresh()->delivered_quantity)->toBeNull();

    Livewire::test(ListOrders::class)->assertActionHidden(TestAction::make('set_status')->table($order));
});

test('denies recording delivery after the permission is revoked in an open dialog', function () {
    $user = supplierDeliveryUser();
    $this->actingAs($user);
    $order = Order::factory()->create(['amount' => 10, 'status' => 'ordered']);
    $page = Livewire::test(ListOrders::class)->mountAction(TestAction::make('set_status')->table($order))
        ->fillForm(['status' => 'partially_delivered', 'delivered_quantity' => 3], 'mountedActionSchema0');
    $user->revokePermissionTo('can-change-order-status');

    $page->callMountedAction();
    expect($order->fresh()->status)->toBe('ordered');
    expect($order->fresh()->delivered_quantity)->toBeNull();
});

test('keeps supplier delivery separate from partial issue to the requester', function () {
    $this->actingAs(supplierDeliveryUser());
    $order = Order::factory()->create(['amount' => 10, 'status' => 'delivered']);

    $order->update(['status' => 'partially_received']);
    expect($order->fresh()->status)->toBe('partially_received');
    expect($order->fresh()->delivered_quantity)->toBe(10);
});

test('displays supplier progress and filters partially delivered orders', function () {
    $this->actingAs(supplierDeliveryUser());
    $partial = Order::factory()->create(['amount' => 10, 'status' => 'partially_delivered', 'delivered_quantity' => 4]);
    $ordered = Order::factory()->create(['amount' => 10, 'status' => 'ordered', 'order_event_id' => $partial->order_event_id, 'department_id' => $partial->department_id]);

    Livewire::test(ListOrders::class)->call('loadTable')->filterTable('status', ['values' => ['partially_delivered']])->assertCanSeeTableRecords([$partial])->assertCanNotSeeTableRecords([$ordered])
        ->assertSee('4 / 10');
});

test('leaves historical unknown quantities unspecified', function () {
    $this->actingAs(supplierDeliveryUser());
    $order = Order::factory()->create(['amount' => 10, 'status' => 'ordered']);

    expect($order->fresh()->supplierDeliveryProgress())->toBe('— / 10');
});

test('edits partial delivery quantities in the order form and displays them on the view page', function () {
    $user = supplierDeliveryUser();
    foreach (['can-edit-all-orders', 'can-always-edit-orders', 'can-create-orders-for-other-departments'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->actingAs($user);
    $order = Order::factory()->create(['amount' => 10, 'status' => 'ordered', 'url' => 'https://example.com/product']);

    Livewire::test(EditOrder::class, ['record' => $order->id])
        ->fillForm(['status' => 'partially_delivered', 'delivered_quantity' => 4])->call('save')->assertHasNoFormErrors();

    expect($order->fresh()->delivered_quantity)->toBe(4);
    Livewire::test(ViewOrder::class, ['record' => $order->id])->assertSee('4 / 10');
});

test('accepts departmental status permission only for its own orders', function () {
    $user = supplierDeliveryUser(false);
    $this->actingAs($user);
    $order = Order::factory()->create(['amount' => 10, 'status' => 'ordered']);
    $role = Role::factory()->create();
    $role->givePermissionTo(Permission::findOrCreate('can-change-order-status', 'web'));
    $user->departmentMemberships()->create(['department_id' => $order->department_id, 'role_id' => $role->id]);

    Livewire::test(ListOrders::class)->callAction(TestAction::make('set_status')->table($order), ['status' => 'partially_delivered', 'delivered_quantity' => 4])
        ->assertHasNoActionErrors();
    expect($order->fresh()->delivered_quantity)->toBe(4);

    $other = Order::factory()->create(['amount' => 10, 'status' => 'ordered']);
    expect(fn () => $other->update(['delivered_quantity' => 3]))->toThrow(HttpException::class);
});
