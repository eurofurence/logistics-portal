<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Logout;

class RemovePushSubscriptionOnLogout
{
    public function handle(Logout $event): void
    {
        if (! $event->user instanceof User || ! request()->hasSession()) {
            return;
        }

        $endpoint = request()->session()->pull('push_subscription_endpoint');
        if (is_string($endpoint)) {
            $event->user->deletePushSubscription($endpoint);
        }
    }
}
