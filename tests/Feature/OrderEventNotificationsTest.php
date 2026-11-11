<?php

use App\Filament\App\Resources\OrderEvents\Pages\EditOrderEvent;
use App\Jobs\SendOrderEventNotification;
use App\Models\Department;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderEventNotificationDelivery as Delivery;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\OrderEventNotification;
use App\Services\LinkedRecordDeletionGuard;
use App\Services\OrderEventNotifications;
use App\Settings\GeneralSettings;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    GeneralSettings::fake(['timezone' => 'Europe/Berlin']);
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:00:00', 'UTC'));
    Notification::fake();
    Queue::fake();
});

/** @return array{user: User, event: OrderEvent, department: Department, role: Role} */
function eventNotificationFixture(): array
{
    Role::findOrCreate('Master', 'web');
    $user = User::factory()->create();
    test()->actingAs($user);
    $department = Department::factory()->create();
    $role = Role::factory()->create();
    $role->givePermissionTo(Permission::findOrCreate('create-Order', 'web'));
    $user->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $role->id]);
    $event = OrderEvent::factory()->create(['order_deadline' => now()->addDays(3), 'is_active' => false]);

    return compact('user', 'event', 'department', 'role');
}

function deliverEventNotifications(): void
{
    foreach (Delivery::query()->where('status', 'pending')->get() as $delivery) {
        (new SendOrderEventNotification($delivery->id))->handle(app(OrderEventNotifications::class));
    }
}

test('new events default to a seven day reminder and disabled opening announcement', function () {
    ['event' => $event] = eventNotificationFixture();
    $event->refresh();
    expect($event->deadline_reminder_enabled)->toBeTrue()
        ->and($event->deadline_reminder_days)->toBe(7)
        ->and($event->deadline_reminder_channels)->toBe(['mail', 'database'])
        ->and($event->opening_notification_enabled)->toBeFalse();
});

test('upgrading existing events leaves automatic reminders disabled', function () {
    ['event' => $event] = eventNotificationFixture();
    $migration = require database_path('migrations/2026_11_11_160754_add_notification_settings_to_order_events_table.php');
    $migration->down();
    $migration->up();
    expect($event->fresh()->deadline_reminder_enabled)->toBeFalse()
        ->and($event->fresh()->deadline_reminder_channels)->toBe(['mail', 'database']);
    $new = OrderEvent::factory()->create();
    expect($new->fresh()->deadline_reminder_enabled)->toBeTrue();
});

test('relative reminders subtract local calendar days across daylight saving changes', function () {
    ['event' => $event] = eventNotificationFixture();
    $event->order_deadline = CarbonImmutable::parse('2026-10-27 12:00', 'Europe/Berlin')->utc();
    expect(app(OrderEventNotifications::class)->plannedAt($event, 'deadline')->toISOString())
        ->toBe('2026-10-20T10:00:00.000000Z');
});

test('automatic reminders deduplicate users and channels regardless of active event or existing orders', function () {
    ['user' => $user, 'event' => $event, 'department' => $department, 'role' => $role] = eventNotificationFixture();
    $user->departmentMemberships()->create(['department_id' => Department::factory()->create()->id, 'role_id' => $role->id]);
    Order::factory()->createQuietly(['department_id' => $department->id, 'order_event_id' => $event->id, 'added_by' => $user->id, 'edited_by' => $user->id]);
    $this->artisan('events:send-order-notifications')->assertSuccessful();
    $this->artisan('events:send-order-notifications')->assertSuccessful();
    $this->assertDatabaseCount('order_event_notification_deliveries', 2);
    deliverEventNotifications();
    expect(Delivery::query()->where('status', 'sent')->count())->toBe(2);
    Notification::assertSentTo($user, OrderEventNotification::class, fn ($notification, $channels): bool => $channels === ['mail']);
    Notification::assertSentTo($user, OrderEventNotification::class, fn ($notification, $channels): bool => $channels === ['database']);
});

test('includes catalog and global order permissions but excludes locked users and deleted departments', function () {
    ['user' => $user] = eventNotificationFixture();
    $catalog = User::factory()->create();
    $catalogRole = Role::factory()->create();
    $catalogRole->givePermissionTo(Permission::findOrCreate('can-place-order', 'web'));
    $department = Department::factory()->create();
    $catalog->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $catalogRole->id]);
    $global = User::factory()->create();
    $global->givePermissionTo(Permission::findOrCreate('can-create-orders-for-other-departments', 'web'));
    $master = User::factory()->create();
    $master->assignRole('Master');
    $service = app(OrderEventNotifications::class);

    expect($service->isRecipient($user))->toBeTrue()
        ->and($service->isRecipient($catalog))->toBeTrue()
        ->and($service->isRecipient($global))->toBeTrue()
        ->and($service->isRecipient($master))->toBeTrue()
        ->and($service->isRecipient(User::factory()->create()))->toBeFalse();
    Department::query()->whereKey($department->id)->update(['deleted_at' => now()]);
    expect($service->isRecipient($catalog->fresh()))->toBeFalse();
    $user->update(['locked' => true]);
    expect($service->isRecipient($user))->toBeFalse();
});

test('waits for unlocked events and never sends after the deadline', function () {
    ['event' => $event] = eventNotificationFixture();
    $event->update(['locked' => true]);
    app(OrderEventNotifications::class)->schedule();
    $this->assertDatabaseCount('order_event_notification_deliveries', 0);
    $event->update(['locked' => false]);
    app(OrderEventNotifications::class)->schedule();
    $this->assertDatabaseCount('order_event_notification_deliveries', 2);
    $this->travel(4)->days();
    deliverEventNotifications();
    Notification::assertNothingSent();
    expect(Delivery::query()->where('status', 'cancelled')->count())->toBe(2);
});

test('events without deadlines have no reminders but may announce ordering', function () {
    ['event' => $event] = eventNotificationFixture();
    $event->update(['order_deadline' => null, 'opening_notification_enabled' => true,
        'opening_notification_mode' => 'scheduled', 'opening_notification_at' => now()->subMinute()]);
    app(OrderEventNotifications::class)->schedule();
    expect(Delivery::query()->pluck('type')->unique()->all())->toBe(['opening']);
    deliverEventNotifications();
    Notification::assertCount(2);
});

test('manual reminders work while disabled and suppress automatic duplicates for the same deadline', function () {
    ['event' => $event] = eventNotificationFixture();
    $service = app(OrderEventNotifications::class);
    $event->update(['deadline_reminder_enabled' => false]);
    $service->enqueue($event, 'deadline', false);
    deliverEventNotifications();
    $event->update(['deadline_reminder_enabled' => true, 'deadline_reminder_days' => 5]);
    $service->schedule();
    $this->assertDatabaseCount('order_event_notification_deliveries', 2);
    $event->update(['order_deadline' => now()->addDays(4)]);
    $service->schedule();
    $this->assertDatabaseCount('order_event_notification_deliveries', 4);
    $service->enqueue($event, 'deadline', false);
    $this->assertDatabaseCount('order_event_notification_deliveries', 6);
});

test('jobs recheck event settings recipients and deadline changes before delivery', function (string $change) {
    ['event' => $event, 'user' => $user] = eventNotificationFixture();
    app(OrderEventNotifications::class)->schedule();
    match ($change) {
        'disabled' => $event->update(['deadline_reminder_enabled' => false]),
        'deadline' => $event->update(['order_deadline' => now()->addDays(4)]),
        'permission' => $user->departmentMemberships()->delete(),
        'deleted' => OrderEvent::query()->whereKey($event->id)->update(['deleted_at' => now()]),
    };
    deliverEventNotifications();
    Notification::assertNothingSent();
    expect(Delivery::query()->where('status', 'cancelled')->count())->toBe(2);
})->with(['disabled', 'deadline', 'permission', 'deleted']);

test('queued deliveries wait through a temporary event lock', function () {
    ['event' => $event] = eventNotificationFixture();
    app(OrderEventNotifications::class)->schedule();
    $event->update(['locked' => true]);
    deliverEventNotifications();
    Notification::assertNothingSent();
    expect(Delivery::query()->where('status', 'pending')->count())->toBe(2);
    $event->update(['locked' => false]);
    deliverEventNotifications();
    Notification::assertCount(2);
});

test('a successful channel is not resent when another channel is retried', function () {
    ['event' => $event] = eventNotificationFixture();
    app(OrderEventNotifications::class)->schedule();
    $mail = Delivery::query()->where('channel', 'mail')->firstOrFail();
    $database = Delivery::query()->where('channel', 'database')->firstOrFail();
    (new SendOrderEventNotification($database->id))->handle(app(OrderEventNotifications::class));
    Notification::partialMock()->shouldReceive('sendNow')->once()->andThrow(new RuntimeException('Mail unavailable'));
    expect(fn () => (new SendOrderEventNotification($mail->id))->handle(app(OrderEventNotifications::class)))
        ->toThrow(RuntimeException::class, 'Mail unavailable');
    expect($mail->fresh()->last_error)->toBe('Mail unavailable')
        ->and($database->fresh()->status)->toBe('sent');
    Notification::fake();
    deliverEventNotifications();
    Notification::assertCount(1);
    expect($mail->fresh()->status)->toBe('sent');
});

test('delivery history prevents event deletion', function () {
    ['event' => $event] = eventNotificationFixture();
    app(OrderEventNotifications::class)->schedule();
    expect(app(LinkedRecordDeletionGuard::class)->hasLinks($event))->toBeTrue()
        ->and($event->delete())->toBeFalse();
});

test('test email permission requires edit access and is available to Master', function () {
    ['event' => $event, 'user' => $user] = eventNotificationFixture();
    expect(Gate::forUser($user)->allows('sendTestEmail', $event))->toBeFalse();
    $user->givePermissionTo('send-test-email-OrderEvent');
    expect(Gate::forUser($user->fresh())->allows('sendTestEmail', $event))->toBeFalse();
    $user->givePermissionTo(Permission::findOrCreate('update-OrderEvent', 'web'));
    expect(Gate::forUser($user->fresh())->allows('sendTestEmail', $event))->toBeTrue();
    $master = User::factory()->create();
    $master->assignRole('Master');
    expect(Gate::forUser($master)->allows('sendTestEmail', $event))->toBeTrue();
});

test('test buttons send only mail to the actor without productive delivery history', function () {
    ['event' => $event, 'user' => $user] = eventNotificationFixture();
    $user->assignRole('Master');
    $event->update(['locked' => true, 'deadline_reminder_enabled' => false]);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Livewire::test(EditOrderEvent::class, ['record' => $event->id])
        ->callAction(TestAction::make('deadlineTest')->schemaComponent('deadline_reminder_actions'))
        ->assertNotified();
    Notification::assertSentTo($user, OrderEventNotification::class,
        fn (OrderEventNotification $notification, array $channels): bool => $notification->test && $channels === ['mail']);
    Notification::assertCount(1);
    $this->assertDatabaseCount('order_event_notification_deliveries', 0);
});

test('notification actions require saving dirty form data', function () {
    ['event' => $event, 'user' => $user] = eventNotificationFixture();
    $user->assignRole('Master');
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Livewire::test(EditOrderEvent::class, ['record' => $event->id])
        ->fillForm(['name' => 'Unsaved name'])
        ->callAction(TestAction::make('deadlineTest')->schemaComponent('deadline_reminder_actions'))
        ->assertHasErrors(['data.name']);
    Notification::assertNothingSent();
});

test('fixed dates must precede the deadline and new dates must be in the future', function () {
    ['event' => $event, 'user' => $user] = eventNotificationFixture();
    $user->assignRole('Master');
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Livewire::test(EditOrderEvent::class, ['record' => $event->id])
        ->fillForm(['deadline_reminder_mode' => 'fixed', 'deadline_reminder_at' => '2026-10-23 12:00:00'])
        ->call('save')->assertHasFormErrors(['deadline_reminder_at']);
    Livewire::test(EditOrderEvent::class, ['record' => $event->id])
        ->fillForm(['opening_notification_mode' => 'scheduled', 'opening_notification_enabled' => true,
            'opening_notification_at' => '2026-11-01 12:00:00'])
        ->call('save')->assertHasFormErrors(['opening_notification_at']);
});

test('unchanged past schedules may be saved and future schedules persist in UTC', function () {
    ['event' => $event, 'user' => $user] = eventNotificationFixture();
    $user->assignRole('Master');
    $event->update(['deadline_reminder_mode' => 'fixed', 'deadline_reminder_at' => now()->subHour()]);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Livewire::test(EditOrderEvent::class, ['record' => $event->id])
        ->fillForm(['name' => 'Updated event'])
        ->call('save')->assertHasNoFormErrors();
    expect($event->fresh()->name)->toBe('Updated event');
    Livewire::test(EditOrderEvent::class, ['record' => $event->id])
        ->fillForm(['deadline_reminder_at' => '2026-10-25 12:00:00'])
        ->call('save')->assertHasNoFormErrors();
    expect($event->fresh()->deadline_reminder_at->toISOString())->toBe('2026-10-25T11:00:00.000000Z');
});

test('edit access alone does not expose test email actions', function () {
    ['event' => $event, 'user' => $user] = eventNotificationFixture();
    foreach (['view-any-OrderEvent', 'update-OrderEvent'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Livewire::test(EditOrderEvent::class, ['record' => $event->id])
        ->assertSuccessful()
        ->assertActionDoesNotExist(TestAction::make('deadlineTest')->schemaComponent('deadline_reminder_actions'))
        ->assertActionVisible(TestAction::make('deadlineSend')->schemaComponent('deadline_reminder_actions'));
});

test('selected channels are respected and enabling opening announcements does not send without a schedule', function () {
    ['event' => $event] = eventNotificationFixture();
    $event->update(['deadline_reminder_channels' => ['database'], 'opening_notification_enabled' => true]);
    app(OrderEventNotifications::class)->schedule();
    deliverEventNotifications();
    expect(Delivery::query()->pluck('channel')->all())->toBe(['database'])
        ->and(Delivery::query()->pluck('type')->all())->toBe(['deadline']);
    Notification::assertCount(1);
});

test('department permissions for another authentication guard do not qualify recipients', function () {
    ['department' => $department] = eventNotificationFixture();
    $user = User::factory()->create();
    $role = Role::factory()->create(['guard_name' => 'api']);
    $role->givePermissionTo(Permission::findOrCreate('create-Order', 'api'));
    $user->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $role->id]);
    expect(app(OrderEventNotifications::class)->isRecipient($user))->toBeFalse();
});

test('notifications use the existing email branding and link database messages to the catalog', function () {
    ['event' => $event, 'user' => $user] = eventNotificationFixture();
    $notification = new OrderEventNotification($event, 'deadline', true);
    $mail = $notification->toMail($user);
    expect($mail->subject)->toStartWith('[TEST] ')
        ->and($mail->view)->toBe('emails.GeneralNotification')
        ->and($mail->viewData)->toHaveKeys(['emailLogo', 'emailColors']);
    $payload = $notification->toDatabase($user);
    expect($payload['format'])->toBe('filament')
        ->and($payload['actions'][0]['url'])->toBe(route('filament.app.resources.order-articles.index'));
});
