<?php

use App\Filament\App\Resources\Orders\Pages\ListOrders;
use App\Models\Department;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('shows the approve action only when event restrictions and permissions allow approval', function (?string $deadline, bool $locked, bool $canApprove, bool $alwaysApprove, string $status, bool $expected) {
    $this->travelTo(now()->setDate(2026, 9, 16)->setTime(12, 0));
    $user = User::factory()->create();
    $this->actingAs($user);
    $user->givePermissionTo(Permission::findOrCreate('view-any-Order', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('can-see-all-orders', 'web'));
    if ($alwaysApprove) {
        $user->givePermissionTo(Permission::findOrCreate('can-always-approve-orders', 'web'));
    }
    $department = Department::factory()->create();
    $role = Role::factory()->create();
    if ($canApprove) {
        $role->givePermissionTo(Permission::findOrCreate('can-approve-orders', 'web'));
    }
    $user->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $role->id]);
    $event = OrderEvent::factory()->make(['order_deadline' => $deadline, 'locked' => $locked]);
    $order = new Order(['department_id' => $department->id, 'status' => $status]);
    $order->setRelation('event', $event);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $action = Livewire::test(ListOrders::class)->instance()->getTable()->getAction('approve')->record($order);

    expect($action->isVisible())->toBe($expected);
})->with([
    'no deadline' => [null, false, true, false, 'awaiting_approval', true],
    'future deadline' => ['2026-09-17 12:00:00', false, true, false, 'awaiting_approval', true],
    'expired deadline' => ['2026-09-15 12:00:00', false, true, false, 'awaiting_approval', false],
    'locked event' => [null, true, true, false, 'awaiting_approval', false],
    'missing permission' => [null, false, false, false, 'awaiting_approval', false],
    'deadline override' => ['2026-09-15 12:00:00', false, true, true, 'awaiting_approval', true],
    'override without approval permission' => [null, false, false, true, 'awaiting_approval', false],
    'already approved' => [null, false, true, true, 'open', false],
]);
