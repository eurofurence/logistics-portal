<?php

use App\Filament\Pages\Auth\EditProfile;
use App\Models\User;
use App\Notifications\DatabaseNotificationPush;
use App\Settings\LoginSettings;
use Filament\Facades\Filament;
use Filament\Livewire\Topbar;
use Filament\Notifications\DatabaseNotification;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Minishlink\WebPush\VAPID;
use NotificationChannels\WebPush\WebPushChannel;
use Symfony\Component\DomCrawler\Crawler;

beforeEach(function () {
    config(['webpush.vapid.public_key' => 'public', 'webpush.vapid.private_key' => 'private']);
    app(LoginSettings::class)->whitelist_active = false;
});

function pushSubscriptionPayload(string $endpoint = 'https://fcm.googleapis.com/fcm/send/browser'): array
{
    return ['endpoint' => $endpoint, 'keys' => ['p256dh' => 'B'.str_repeat('a', 86), 'auth' => str_repeat('b', 22)]];
}

test('requires authentication to register and remove push subscriptions', function () {
    $this->postJson(route('push-subscriptions.store'), pushSubscriptionPayload())->assertUnauthorized();
    $this->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => 'endpoint'])->assertUnauthorized();
});

test('registers and updates a browser subscription only for the authenticated user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $payload = pushSubscriptionPayload();
    $payload['subscribable_id'] = $other->id;

    $this->actingAs($user)->postJson(route('push-subscriptions.store'), $payload)->assertNoContent();
    $this->postJson(route('push-subscriptions.store'), $payload)->assertNoContent();

    expect($user->pushSubscriptions()->count())->toBe(1);
    expect($other->pushSubscriptions()->count())->toBe(0);
    $this->assertDatabaseHas('push_subscriptions', ['endpoint' => $payload['endpoint'], 'public_key' => $payload['keys']['p256dh']]);
    expect(session('push_subscription_endpoint'))->toBe($payload['endpoint']);
});

test('moves a shared browser subscription to the currently signed in user', function () {
    $previous = User::factory()->create();
    $current = User::factory()->create();
    $payload = pushSubscriptionPayload();
    $previous->updatePushSubscription($payload['endpoint'], $payload['keys']['p256dh'], $payload['keys']['auth']);

    $this->actingAs($current)->postJson(route('push-subscriptions.store'), $payload)->assertNoContent();

    expect($previous->pushSubscriptions()->exists())->toBeFalse();
    expect($current->pushSubscriptions()->count())->toBe(1);
});

test('rejects unsafe push endpoints with 422', function (string $endpoint) {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('push-subscriptions.store'), pushSubscriptionPayload($endpoint))
        ->assertUnprocessable()->assertJsonValidationErrors('endpoint');

    expect($user->pushSubscriptions()->exists())->toBeFalse();
})->with(['http://fcm.googleapis.com/send', 'https://127.0.0.1/push', 'https://attacker.example/push',
    'https://fcm.googleapis.com.attacker.example/push', 'https://fcm.googleapis.com:8443/push', 'https://user:pass@fcm.googleapis.com/push']);

test('rejects missing subscription keys with 422', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('push-subscriptions.store'), ['endpoint' => pushSubscriptionPayload()['endpoint']])
        ->assertUnprocessable()->assertJsonValidationErrors(['keys', 'keys.p256dh', 'keys.auth']);
});

test('returns 503 when push keys have not been configured', function () {
    config(['webpush.vapid.private_key' => null]);
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('push-subscriptions.store'), pushSubscriptionPayload())->assertServiceUnavailable();
});

test('forbids locked users from registering push subscriptions with 403', function () {
    $user = User::factory()->create(['locked' => true]);

    $this->actingAs($user)->postJson(route('push-subscriptions.store'), pushSubscriptionPayload())->assertForbidden();
});

test('removes only subscriptions owned by the authenticated user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $subscription = $owner->updatePushSubscription(pushSubscriptionPayload()['endpoint']);

    $this->actingAs($other)->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => $subscription->endpoint])->assertNoContent();
    $this->assertModelExists($subscription);
    $this->actingAs($owner)->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => $subscription->endpoint])->assertNoContent();
    $this->assertModelMissing($subscription);
});

test('removes the current browser on logout while retaining other devices', function () {
    $user = User::factory()->create();
    $browser = $user->updatePushSubscription(pushSubscriptionPayload()['endpoint']);
    $otherDevice = $user->updatePushSubscription('https://fcm.googleapis.com/fcm/send/other');
    $this->actingAs($user)->withSession(['push_subscription_endpoint' => $browser->endpoint])
        ->post(route('filament.app.auth.logout'))->assertRedirect();

    $this->assertModelMissing($browser);
    $this->assertModelExists($otherDevice);
});

test('queues a push after a Filament database notification is stored', function () {
    $user = User::factory()->create();
    $user->updatePushSubscription(pushSubscriptionPayload()['endpoint']);
    Queue::fake([SendQueuedNotifications::class]);

    $user->notifyNow(FilamentNotification::make()->title('Order updated')->body('<b>Ready</b> &amp; waiting')->toDatabase());

    $databaseNotification = $user->notifications()->sole();
    Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job) use ($databaseNotification): bool {
        return $job->notification instanceof DatabaseNotificationPush
            && $job->notification->databaseNotificationId === $databaseNotification->id
            && $job->notification->afterCommit === true
            && $job->channels === [WebPushChannel::class];
    });
    $message = (new DatabaseNotificationPush($databaseNotification->id))->toWebPush($user)->toArray();
    expect($message['title'])->toBe('Order updated');
    expect($message['body'])->toBe('Ready & waiting');
    expect($message['tag'])->toBe('database-'.$databaseNotification->id);
    expect($message['data']['url'])->toStartWith('/app');
});

test('keeps database notifications working when push is unavailable', function (bool $configured, bool $subscribed, bool $locked) {
    $user = User::factory()->create(['locked' => $locked]);
    if ($subscribed) {
        $user->updatePushSubscription(pushSubscriptionPayload()['endpoint']);
    }
    if (! $configured) {
        config(['webpush.vapid.private_key' => null]);
    }
    Queue::fake([SendQueuedNotifications::class]);

    $user->notifyNow(FilamentNotification::make()->title('Saved')->toDatabase());

    expect($user->notifications()->count())->toBe(1);
    Queue::assertNothingPushed();
})->with([[false, true, false], [true, false, false], [true, true, true]]);

test('does not push database notifications outside the Filament format', function () {
    $user = User::factory()->create();
    $user->updatePushSubscription(pushSubscriptionPayload()['endpoint']);
    Queue::fake([SendQueuedNotifications::class]);

    $user->notifyNow(new DatabaseNotification(['title' => 'Internal notification']));

    expect($user->notifications()->count())->toBe(1);
    Queue::assertNothingPushed();
});

test('shows push controls only for signed in users and exposes only the public key', function () {
    expect(trim(view('components.push-notifications')->render()))->toBe('');
    $this->actingAs(User::factory()->create());

    $html = view('components.push-notifications')->render();

    expect($html)->toContain('data-public-key="public"', 'x-data="portalPushNotifications"', 'x-on:click="toggle()"', 'x-bind:disabled="actionDisabled"', 'x-text="status"')
        ->not->toContain('private');
    Filament::setCurrentPanel(Filament::getPanel('app'));
    expect(view('components.pwa')->render())->toContain('/push-notifications.js?v=');
    config(['webpush.vapid.private_key' => null]);
    expect(trim(view('components.push-notifications')->render()))->toBe('');
});

test('renders the push popup once at the end of the page without a topbar trigger', function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::bootCurrentPanel();

    $html = Livewire::test(Topbar::class)->html();

    expect($html)->toContain('fi-topbar-database-notifications-btn')->not->toContain('data-push-controls', 'portal-push-settings');
    $popup = FilamentView::renderHook(PanelsRenderHook::BODY_END)->toHtml();
    expect((new Crawler($popup))->filter('#portal-push-settings')->count())->toBe(1);
    expect($popup)->toContain('x-on:open-push-settings.window="openSettings()"', 'modal-closed.window', 'dismissPrompt()')
        ->not->toContain('fi-modal-trigger');
});

test('opens push settings from the notifications tab of the app profile', function () {
    $this->actingAs(User::factory()->create(['two_factor_required' => false]));
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(EditProfile::class)->assertSee(__('pwa.push_manage'))
        ->assertSee('open-push-settings', false);
});

test('does not offer app push settings in the admin profile or without configured keys', function (string $panel, bool $configured) {
    $this->actingAs(User::factory()->create(['two_factor_required' => false]));
    Filament::setCurrentPanel(Filament::getPanel($panel));
    if (! $configured) {
        config(['webpush.vapid.private_key' => null]);
    }

    Livewire::test(EditProfile::class)->assertDontSee(__('pwa.push_manage'));
})->with([['admin', true], ['app', false]]);

test('removes expired browser subscriptions after a push service returns 410', function () {
    $user = User::factory()->create();
    $keys = VAPID::createVapidKeys();
    config(['webpush.vapid.public_key' => $keys['publicKey'], 'webpush.vapid.private_key' => $keys['privateKey'], 'webpush.vapid.subject' => 'https://example.com']);
    $subscription = $user->updatePushSubscription(pushSubscriptionPayload()['endpoint'], $keys['publicKey'], str_repeat('b', 22), 'aes128gcm');
    $notification = $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'test', 'data' => ['title' => 'Saved']]);
    Http::preventStrayRequests();
    Http::fake([$subscription->endpoint => Http::response('', 410)]);

    app(WebPushChannel::class)->send($user, new DatabaseNotificationPush($notification->id));

    $this->assertModelMissing($subscription);
});

test('does not deliver a queued push after the user is locked or the notification is removed', function () {
    $user = User::factory()->create();
    $databaseNotification = $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'test', 'data' => []]);
    $push = new DatabaseNotificationPush($databaseNotification->id);

    expect($push->shouldSend($user, WebPushChannel::class))->toBeTrue();
    $user->locked = true;
    expect($push->shouldSend($user, WebPushChannel::class))->toBeFalse();
    $user->locked = false;
    $databaseNotification->delete();
    expect($push->shouldSend($user, WebPushChannel::class))->toBeFalse();
});
