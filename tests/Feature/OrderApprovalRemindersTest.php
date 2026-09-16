<?php

use App\Models\Department;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\OrderApprovalReminder;
use App\Settings\GeneralSettings;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    GeneralSettings::fake(['timezone' => 'Europe/Berlin']);
    $this->travelTo(CarbonImmutable::parse('2026-09-16 06:00:00', 'UTC'));
});

/**
 * @return array{user: User, department: Department, event: OrderEvent, role: Role}
 */
function approvalReminderFixture(): array
{
    $user = User::factory()->create();
    test()->actingAs($user);
    $department = Department::factory()->create();
    $event = OrderEvent::factory()->create(['order_deadline' => null]);
    $role = Role::factory()->create();
    foreach (['can-approve-orders', 'view-Order'] as $permission) {
        $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $user->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $role->id]);

    return compact('user', 'department', 'event', 'role');
}

/**
 * @param  array{user: User, department: Department, event: OrderEvent, role: Role}  $fixture
 * @param  array<string, mixed>  $attributes
 */
function pendingApprovalOrder(array $fixture, array $attributes = []): Order
{
    return Order::factory()->createQuietly(array_merge([
        'department_id' => $fixture['department']->id,
        'order_event_id' => $fixture['event']->id,
        'added_by' => $fixture['user']->id,
        'edited_by' => $fixture['user']->id,
        'status' => 'awaiting_approval',
    ], $attributes));
}

test('sends one digest per recipient containing only actionable unreported approvals', function () {
    Notification::fake();
    $fixture = approvalReminderFixture();
    $secondRole = Role::factory()->create();
    $secondRole->givePermissionTo('can-approve-orders');
    $fixture['user']->departmentMemberships()->create(['department_id' => $fixture['department']->id, 'role_id' => $secondRole->id]);
    $first = pendingApprovalOrder($fixture);
    $second = pendingApprovalOrder($fixture);
    pendingApprovalOrder($fixture, ['status' => 'open']);
    pendingApprovalOrder($fixture, ['deleted_at' => now()]);
    pendingApprovalOrder($fixture, ['department_id' => Department::factory()->create()->id]);
    pendingApprovalOrder($fixture, ['order_event_id' => OrderEvent::factory()->create(['locked' => true])->id]);
    pendingApprovalOrder($fixture, ['order_event_id' => OrderEvent::factory()->create(['order_deadline' => now()->subDay()])->id]);
    auth()->logout();

    $this->artisan('orders:send-approval-reminders')->assertSuccessful();

    Notification::assertSentTo($fixture['user'], OrderApprovalReminder::class, function (OrderApprovalReminder $notification, array $channels) use ($first, $second): bool {
        return $notification->orders->modelKeys() === [$first->id, $second->id] && $channels === ['mail', 'database'];
    });
    Notification::assertCount(1);
    $this->assertDatabaseCount('order_approval_reminders', 2);
    expect(auth()->check())->toBeFalse();
});

test('sends new orders the next local day without repeating previous reminders', function () {
    Notification::fake();
    GeneralSettings::fake(['timezone' => 'Asia/Tokyo']);
    $this->travelTo(CarbonImmutable::parse('2026-09-16 14:50:00', 'UTC'));
    $fixture = approvalReminderFixture();
    pendingApprovalOrder($fixture);
    $this->artisan('orders:send-approval-reminders')->assertSuccessful();
    $newOrder = pendingApprovalOrder($fixture);

    $this->artisan('orders:send-approval-reminders')->assertSuccessful();
    Notification::assertCount(1);
    $this->travelTo(CarbonImmutable::parse('2026-09-16 15:10:00', 'UTC'));
    $this->artisan('orders:send-approval-reminders')->assertSuccessful();

    Notification::assertCount(2);
    expect(Notification::sent($fixture['user'], OrderApprovalReminder::class)->last()->orders->modelKeys())->toBe([$newOrder->id]);
    $this->travel(1)->days();
    $this->artisan('orders:send-approval-reminders')->assertSuccessful();
    Notification::assertCount(2);
});

test('tracks reminders individually for approvers added later', function () {
    Notification::fake();
    $fixture = approvalReminderFixture();
    $order = pendingApprovalOrder($fixture);
    $this->artisan('orders:send-approval-reminders')->assertSuccessful();
    $newApprover = User::factory()->create();
    $newApprover->departmentMemberships()->create(['department_id' => $fixture['department']->id, 'role_id' => $fixture['role']->id]);

    $this->artisan('orders:send-approval-reminders')->assertSuccessful();

    Notification::assertSentToTimes($fixture['user'], OrderApprovalReminder::class, 1);
    Notification::assertSentToTimes($newApprover, OrderApprovalReminder::class, 1);
    $this->assertDatabaseHas('order_approval_reminders', ['user_id' => $newApprover->id, 'order_id' => $order->id]);
});

test('does not remind locked users or users missing viewing or approval permission', function (string $restriction) {
    Notification::fake();
    $fixture = approvalReminderFixture();
    pendingApprovalOrder($fixture);
    if ($restriction === 'locked') {
        $fixture['user']->update(['locked' => true]);
    } else {
        $fixture['role']->revokePermissionTo($restriction);
    }

    $this->artisan('orders:send-approval-reminders')->assertSuccessful();

    Notification::assertNothingSent();
    $this->assertDatabaseCount('order_approval_reminders', 0);
})->with(['locked', 'view-Order', 'can-approve-orders']);

test('keeps failed deliveries eligible for retry and releases the command lock', function () {
    $fixture = approvalReminderFixture();
    pendingApprovalOrder($fixture);
    Notification::shouldReceive('sendNow')->once()->andThrow(new RuntimeException('Mail transport unavailable'));

    $this->artisan('orders:send-approval-reminders')->assertFailed();

    $this->assertDatabaseCount('order_approval_reminders', 0);
    Notification::fake();
    $this->artisan('orders:send-approval-reminders')->assertSuccessful();
    Notification::assertSentToTimes($fixture['user'], OrderApprovalReminder::class, 1);
});

test('renders escaped order details and uses the users notification email', function () {
    $fixture = approvalReminderFixture();
    $fixture['user']->notification_email = 'approvals@example.com';
    $order = pendingApprovalOrder($fixture, ['name' => '<script>alert(1)</script>']);
    $notification = new OrderApprovalReminder(Order::with(['department', 'event'])->whereKey($order->id)->get());

    $html = $notification->toMail($fixture['user'])->render();

    expect((string) $html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->toContain(route('filament.app.resources.orders.view', $order))
        ->not->toContain('<script>alert(1)</script>');
    expect($fixture['user']->routeNotificationForMail($notification))->toBe('approvals@example.com');
    expect($notification->toDatabase($fixture['user'])['format'])->toBe('filament');
});

test('schedules approval digests at eight in the configured timezone', function () {
    GeneralSettings::fake(['timezone' => 'America/New_York']);
    $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'orders:send-approval-reminders'));

    expect($event->nextRunDate(now())->utc()->toDateTimeString())->toBe('2026-09-16 12:00:00');
});

test('includes cross department approvals and deadline overrides only for authorized recipients', function () {
    Notification::fake();
    $fixture = approvalReminderFixture();
    $order = pendingApprovalOrder($fixture, [
        'department_id' => Department::factory()->create()->id,
        'order_event_id' => OrderEvent::factory()->create(['locked' => true, 'order_deadline' => now()->subDay()])->id,
    ]);
    $globalApprover = User::factory()->create();
    foreach (['can-see-all-orders', 'can-approve-orders-for-other-departments', 'can-always-approve-orders'] as $permission) {
        $globalApprover->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    auth()->logout();

    $this->artisan('orders:send-approval-reminders')->assertSuccessful();

    Notification::assertSentTo($globalApprover, OrderApprovalReminder::class, fn (OrderApprovalReminder $notification): bool => $notification->orders->modelKeys() === [$order->id]);
    Notification::assertNotSentTo($fixture['user'], OrderApprovalReminder::class);
});

test('does not send while another reminder command holds the lock', function () {
    Notification::fake();
    $fixture = approvalReminderFixture();
    pendingApprovalOrder($fixture);
    $lock = Cache::lock('orders:send-approval-reminders', 86400);
    $lock->get();

    try {
        $this->artisan('orders:send-approval-reminders')->assertSuccessful();
        Notification::assertNothingSent();
        $this->assertDatabaseCount('order_approval_reminders', 0);
    } finally {
        $lock->release();
    }
});
