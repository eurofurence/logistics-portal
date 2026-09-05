<?php

use App\Events\BillCreated;
use App\Models\Bill;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->travelTo(now('Europe/Berlin')->setDate(2026, 9, 5)->setTime(8, 0));
    $this->submitter = User::factory()->create();
    $this->actingAs($this->submitter);
    $this->accountant = User::factory()->create();
    $this->permission = Permission::findOrCreate('get-bill-payment-reminder-accountant-notification', 'web');
    $this->accountant->givePermissionTo($this->permission);

    Event::fake([BillCreated::class]);
    Notification::fake();
});

test('reminds every authorized accountant through the existing notification channels', function () {
    $role = Role::factory()->create(['guard_name' => 'web']);
    $role->givePermissionTo($this->permission);
    $secondAccountant = User::factory()->create();
    $secondAccountant->assignRole($role);
    $unsubscribedAccountant = User::factory()->create();
    $unsubscribedAccountant->givePermissionTo(Permission::findOrCreate('get-new-bill-accountant-notification', 'web'));
    $bill = Bill::factory()->create(['payment_deadline' => '2026-09-12']);
    auth()->logout();

    $this->artisan('bills:send-payment-reminders')->assertSuccessful();

    foreach ([$this->accountant, $secondAccountant] as $accountant) {
        Notification::assertSentTo($accountant, GeneralNotification::class, function (GeneralNotification $notification) use ($accountant, $bill): bool {
            $data = $notification->toArray($accountant)['data'];

            return str_contains($data['message'], '12.09.2026')
                && $data['details']['link'] === route('filament.app.resources.bills.view', $bill)
                && $data['details']['title'] === $bill->title;
        });
        Notification::assertSentTo($accountant, DatabaseNotification::class);
    }

    Notification::assertNotSentTo($unsubscribedAccountant, GeneralNotification::class);
    Notification::assertNotSentTo($this->submitter, GeneralNotification::class);
    expect($bill->fresh()->payment_reminder_sent_at)->not->toBeNull()
        ->and($bill->fresh()->payment_overdue_reminder_sent_at)->toBeNull();
});

test('respects deadline boundaries and terminal invoice statuses', function (?string $deadline, string $status, bool $send, bool $overdue) {
    $bill = Bill::factory()->create(['payment_deadline' => $deadline, 'status' => $status]);
    auth()->logout();

    $this->artisan('bills:send-payment-reminders')->assertSuccessful();

    if (! $send) {
        Notification::assertNothingSent();

        return;
    }

    Notification::assertSentTo($this->accountant, GeneralNotification::class, function (GeneralNotification $notification) use ($overdue): bool {
        return str_contains($notification->toArray($this->accountant)['data']['message'], 'expired') === $overdue;
    });
    expect($bill->fresh()->getAttribute($overdue ? 'payment_overdue_reminder_sent_at' : 'payment_reminder_sent_at'))->not->toBeNull();
})->with([
    'no deadline' => [null, 'open', false, false],
    'eight days away' => ['2026-09-13', 'open', false, false],
    'seven days away' => ['2026-09-12', 'open', true, false],
    'submitted on short notice' => ['2026-09-07', 'open', true, false],
    'due today' => ['2026-09-05', 'open', true, false],
    'expired yesterday' => ['2026-09-04', 'open', true, true],
    'long overdue' => ['2026-08-01', 'open', true, true],
    'completed' => ['2026-09-04', 'done', false, false],
    'rejected' => ['2026-09-04', 'rejected', false, false],
    'on hold' => ['2026-09-12', 'on_hold', true, false],
    'checking' => ['2026-09-12', 'checking', true, false],
    'processing' => ['2026-09-12', 'processing', true, false],
]);

test('sends only one reminder per stage even across repeated daily runs', function () {
    Bill::factory()->create(['payment_deadline' => '2026-09-12']);
    auth()->logout();

    $this->artisan('bills:send-payment-reminders')->assertSuccessful();
    $this->artisan('bills:send-payment-reminders')->assertSuccessful();
    $this->travel(1)->days();
    $this->artisan('bills:send-payment-reminders')->assertSuccessful();
    Notification::assertSentToTimes($this->accountant, GeneralNotification::class, 1);

    $this->travel(7)->days();
    $this->artisan('bills:send-payment-reminders')->assertSuccessful();
    $this->travel(1)->days();
    $this->artisan('bills:send-payment-reminders')->assertSuccessful();
    Notification::assertSentToTimes($this->accountant, GeneralNotification::class, 2);
    Notification::assertSentToTimes($this->accountant, DatabaseNotification::class, 2);
});

test('releases reminders again when the payment deadline changes', function () {
    $bill = Bill::factory()->create(['payment_deadline' => '2026-09-04']);
    $this->artisan('bills:send-payment-reminders')->assertSuccessful();

    $bill->refresh()->update(['payment_deadline' => '2026-09-11']);
    expect($bill->fresh()->payment_deadline->toDateString())->toBe('2026-09-11')
        ->and($bill->fresh()->payment_reminder_sent_at)->toBeNull()
        ->and($bill->fresh()->payment_overdue_reminder_sent_at)->toBeNull();

    $this->artisan('bills:send-payment-reminders')->assertSuccessful();
    Notification::assertSentToTimes($this->accountant, GeneralNotification::class, 2);
});

test('does not remind about deleted invoices', function () {
    Bill::factory()->create(['payment_deadline' => '2026-09-04'])->delete();
    $this->artisan('bills:send-payment-reminders')->assertSuccessful();
    Notification::assertNothingSent();
});

test('keeps the reminder pending when there are no recipients', function () {
    $bill = Bill::factory()->create(['payment_deadline' => '2026-09-12']);
    $this->accountant->revokePermissionTo($this->permission);
    $this->artisan('bills:send-payment-reminders')->assertSuccessful();
    Notification::assertNothingSent();
    expect($bill->fresh()->payment_reminder_sent_at)->toBeNull();

    $this->accountant->givePermissionTo($this->permission);
    $this->artisan('bills:send-payment-reminders')->assertSuccessful();
    Notification::assertSentTo($this->accountant, GeneralNotification::class);
});

test('does not copy reminder history when an invoice is duplicated', function () {
    $bill = Bill::factory()->create(['payment_deadline' => '2026-09-12']);
    $this->artisan('bills:send-payment-reminders')->assertSuccessful();

    $replica = $bill->fresh()->replicate();
    $replica->save();

    expect($replica->payment_reminder_sent_at)->toBeNull()
        ->and($replica->payment_overdue_reminder_sent_at)->toBeNull();
});

test('does not run while another reminder process holds the lock', function () {
    $bill = Bill::factory()->create(['payment_deadline' => '2026-09-12']);
    $lock = Cache::lock('bills:send-payment-reminders', 86400);
    expect($lock->get())->toBeTrue();

    try {
        $this->artisan('bills:send-payment-reminders')->assertSuccessful();
        Notification::assertNothingSent();
        expect($bill->fresh()->payment_reminder_sent_at)->toBeNull();
    } finally {
        $lock->release();
    }
});

test('keeps failed reminders pending and releases the lock for a retry', function () {
    $bill = Bill::factory()->create(['payment_deadline' => '2026-09-12']);
    Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Delivery failed'));

    expect(fn () => $this->artisan('bills:send-payment-reminders')->run())
        ->toThrow(RuntimeException::class, 'Delivery failed');
    expect($bill->fresh()->payment_reminder_sent_at)->toBeNull();

    Notification::fake();
    $this->artisan('bills:send-payment-reminders')->assertSuccessful();
    Notification::assertSentTo($this->accountant, GeneralNotification::class);
});
