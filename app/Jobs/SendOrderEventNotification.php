<?php

namespace App\Jobs;

use App\Models\OrderEventNotificationDelivery as Delivery;
use App\Notifications\OrderEventNotification;
use App\Services\OrderEventNotifications;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendOrderEventNotification implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $uniqueFor = 3600;

    public int $timeout = 60;

    public function __construct(public int $deliveryId)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return (string) $this->deliveryId;
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(OrderEventNotifications $service): void
    {
        Cache::lock('event-delivery:'.$this->deliveryId, 120)->block(5, function () use ($service): void {
            $delivery = Delivery::query()->find($this->deliveryId);
            if (! $delivery || in_array($delivery->status, ['sent', 'cancelled'], true)) {
                return;
            }
            $event = $delivery->event;
            $user = $delivery->user;
            $prefix = $service->prefix($delivery->type);
            if (! $event || ! $user || ! $service->isRecipient($user)
                || ($event->order_deadline && ! $event->order_deadline->isFuture())
                || $delivery->cycle_key !== $service->cycleKey($event, $delivery->type)
                || ($delivery->automatic && (! $event->{$prefix.'_enabled'}
                    || ! in_array($delivery->channel, $event->{$prefix.'_channels'} ?? ['mail', 'database'], true)
                    || ! $service->plannedAt($event, $delivery->type)?->lte(now())))) {
                $delivery->update(['status' => 'cancelled']);

                return;
            }
            if ($event->locked) {
                return;
            }
            $delivery->increment('attempts');
            try {
                Notification::sendNow($user, new OrderEventNotification($event, $delivery->type), [$delivery->channel]);
                $delivery->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
            } catch (Throwable $exception) {
                $delivery->update(['last_error' => mb_substr($exception->getMessage(), 0, 2000)]);
                throw $exception;
            }
        });
    }

    public function failed(?Throwable $exception): void
    {
        Delivery::query()->whereKey($this->deliveryId)->where('status', 'pending')
            ->update(['status' => 'failed', 'last_error' => mb_substr($exception?->getMessage() ?? 'Queue failure', 0, 2000)]);
    }
}
