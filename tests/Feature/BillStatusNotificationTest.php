<?php

use App\Events\BillCreated;
use App\Events\BillStatusChanged;
use App\Filament\App\Resources\Bills\Tables\BillsTable;
use App\Listeners\SendBillStatusChangedNotification;
use App\Models\Bill;
use App\Models\Permission;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

test('preserves the status transition and comment notice when a queued event is processed later', function (?string $comment, bool $hasComment) {
    Event::fake([BillCreated::class, BillStatusChanged::class]);
    Notification::fake();
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('can-change-bill-status', 'web'));
    $this->actingAs($user);
    $bill = Bill::factory()->create(['added_by' => $user->id, 'status' => 'open']);

    $bill->update(['status' => 'done', 'comment' => $comment]);

    Event::assertDispatchedTimes(BillStatusChanged::class, 1);
    $event = Event::dispatched(BillStatusChanged::class)->first()[0];
    expect($event->previousStatus)->toBe('open');
    expect($event->newStatus)->toBe('done');
    expect($event->hasComment)->toBe($hasComment);
    $serializedEvent = serialize($event);
    $bill->updateQuietly(['status' => 'rejected', 'comment' => null]);

    app(SendBillStatusChangedNotification::class)->handle(unserialize($serializedEvent));

    Notification::assertSentTo($user, GeneralNotification::class, function (GeneralNotification $notification) use ($user, $bill, $hasComment): bool {
        $data = $notification->toArray($user)['data'];
        $html = (string) $notification->toMail($user)->render();
        expect($data['message'])->toBe('The status of your bill changed from Open to Done.');
        expect($data['details']['link'])->toBe(route('filament.app.resources.bills.view', $bill));
        expect($html)->toContain('from Open to Done')->not->toContain('PRIVATE COMMENT');
        if ($hasComment) {
            expect($html)->toContain('Please log in and review the comments on your bill.');
        } else {
            expect($data['details']['message'])->toBeNull();
            expect($html)->not->toContain('Please log in');
        }

        return true;
    });
})->with([
    'with comment' => ['PRIVATE COMMENT', true],
    'without comment' => [null, false],
]);

test('does not send status notifications when only a comment changes', function () {
    Event::fake([BillCreated::class, BillStatusChanged::class]);
    $this->actingAs(User::factory()->create());
    $bill = Bill::factory()->create(['status' => 'open']);

    $bill->update(['comment' => 'Additional information']);

    Event::assertNotDispatched(BillStatusChanged::class);
});

test('marks bills with comments in the list without revealing comment text', function (?string $comment, ?string $expected) {
    $bill = new Bill(['comment' => $comment]);
    $column = collect(BillsTable::getColumns())->first(fn ($column) => $column->getName() === 'title');
    app()->setLocale('en');

    $description = $column->record($bill)->getDescriptionBelow();

    expect($description)->toBe($expected);
})->with([
    'comment present' => ['PRIVATE COMMENT', 'Comment'],
    'zero is a comment' => ['0', 'Comment'],
    'no comment' => [null, null],
    'empty comment' => ['', null],
    'whitespace only' => ['   ', null],
]);
