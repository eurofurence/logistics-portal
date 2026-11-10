<?php

namespace App\Http\Controllers;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\PushSubscription;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): Response
    {
        abort_unless(filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key')), 503);

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:'.PushSubscription::ENDPOINT_MAX_LENGTH, 'url:https', function (string $attribute, mixed $value, Closure $fail): void {
                $url = parse_url($value);
                if (! is_array($url) || isset($url['user']) || isset($url['pass']) || isset($url['fragment'])
                    || (isset($url['port']) && $url['port'] !== 443)
                    || ! Str::is(config('webpush.allowed_endpoint_hosts'), strtolower($url['host'] ?? ''))) {
                    $fail(__('validation.url', ['attribute' => $attribute]));
                }
            }],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'regex:/^B[A-Za-z0-9_-]{86}={0,1}$/'],
            'keys.auth' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{22}={0,2}$/'],
        ]);

        $subscription = $request->user()->updatePushSubscription(
            $data['endpoint'], $data['keys']['p256dh'], $data['keys']['auth'], 'aes128gcm',
        );
        $request->session()->put('push_subscription_endpoint', $subscription->endpoint);

        return response()->noContent();
    }

    public function destroy(Request $request): Response
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:'.PushSubscription::ENDPOINT_MAX_LENGTH]]);
        $request->user()->deletePushSubscription($data['endpoint']);
        if ($request->session()->get('push_subscription_endpoint') === $data['endpoint']) {
            $request->session()->forget('push_subscription_endpoint');
        }

        return response()->noContent();
    }
}
