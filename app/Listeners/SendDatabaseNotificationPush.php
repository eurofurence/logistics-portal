<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\DatabaseNotificationPush;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;

class SendDatabaseNotificationPush
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database' || ! $event->response instanceof DatabaseNotification
            || ! $event->notifiable instanceof User || $event->notifiable->locked
            || ($event->response->data['format'] ?? null) !== 'filament'
            || blank(config('webpush.vapid.public_key')) || blank(config('webpush.vapid.private_key'))) {
            return;
        }

        if ($event->notifiable->pushSubscriptions()->exists()) {
            $event->notifiable->notify(new DatabaseNotificationPush($event->response->id));
        }
    }
}
