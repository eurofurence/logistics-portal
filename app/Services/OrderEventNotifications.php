<?php

namespace App\Services;

use App\Jobs\SendOrderEventNotification;
use App\Models\Department;
use App\Models\OrderEvent;
use App\Models\OrderEventNotificationDelivery as Delivery;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderEventNotifications
{
    public function prefix(string $type): string
    {
        abort_unless(in_array($type, ['deadline', 'opening'], true), 422);

        return $type === 'deadline' ? 'deadline_reminder' : 'opening_notification';
    }

    public function plannedAt(OrderEvent $event, string $type): ?CarbonImmutable
    {
        if ($type === 'deadline') {
            if (! $event->order_deadline) {
                return null;
            }

            return $event->deadline_reminder_mode === 'relative'
                ? ApplicationTime::local($event->order_deadline)->subDays($event->deadline_reminder_days)->utc()
                : ($event->deadline_reminder_at ? CarbonImmutable::instance($event->deadline_reminder_at) : null);
        }

        return $event->opening_notification_mode === 'scheduled' && $event->opening_notification_at
            ? CarbonImmutable::instance($event->opening_notification_at) : null;
    }

    public function cycleKey(OrderEvent $event, string $type): string
    {
        return hash('sha256', $type.'|'.($type === 'deadline'
            ? $event->order_deadline?->toISOString()
            : $event->opening_notification_at?->toISOString()));
    }

    public function isOpen(OrderEvent $event): bool
    {
        return ! $event->trashed() && ! $event->locked && (! $event->order_deadline || $event->order_deadline->isFuture());
    }

    public function isRecipient(User $user): bool
    {
        if ($user->locked || $user->trashed()) {
            return false;
        }

        if (($user->isSuperAdmin() || $user->can('can-create-orders-for-other-departments')) && Department::query()->exists()) {
            return true;
        }

        return $user->departmentMemberships()->whereHas('department')
            ->whereHas('role.permissions', fn ($query) => $query->where('guard_name', 'web')->whereIn('name', ['create-Order', 'can-place-order']))->exists();
    }

    public function schedule(): void
    {
        OrderEvent::query()->where('locked', false)->lazyById()->each(function (OrderEvent $event): void {
            foreach (['deadline', 'opening'] as $type) {
                $prefix = $this->prefix($type);
                $planned = $this->plannedAt($event, $type);
                if ($event->{$prefix.'_enabled'} && $planned && $planned->lte(now()) && $this->isOpen($event)) {
                    $this->enqueue($event, $type);
                }
            }
        });

        Delivery::query()->where('status', 'pending')
            ->lazyById()->each(fn (Delivery $delivery) => SendOrderEventNotification::dispatch($delivery->id));
    }

    public function enqueue(OrderEvent $event, string $type, bool $automatic = true): int
    {
        $prefix = $this->prefix($type);

        return Cache::lock('event-notifications:'.$event->id, 120)->block(5, function () use ($event, $type, $automatic, $prefix): int {
            $event = $event->fresh();
            if (! $event || ! $this->isOpen($event) || ($type === 'deadline' && ! $event->order_deadline)) {
                throw ValidationException::withMessages(['notification' => __('event-notifications.unavailable')]);
            }
            if ($automatic && (! $event->{$prefix.'_enabled'} || ! $this->plannedAt($event, $type)?->lte(now()))) {
                return 0;
            }
            $cycle = $this->cycleKey($event, $type);
            $batch = $automatic ? $cycle : (string) Str::uuid();
            $count = 0;
            foreach (User::query()->where('locked', false)->lazyById() as $user) {
                if (! $this->isRecipient($user)) {
                    continue;
                }
                foreach (array_intersect($event->{$prefix.'_channels'} ?? ['mail', 'database'], ['mail', 'database']) as $channel) {
                    if ($automatic && Delivery::query()->where('order_event_id', $event->id)->where('user_id', $user->id)
                        ->where('type', $type)->where('cycle_key', $cycle)->where('channel', $channel)
                        ->where('status', '!=', 'cancelled')->exists()) {
                        continue;
                    }
                    $delivery = Delivery::query()->firstOrCreate([
                        'order_event_id' => $event->id, 'user_id' => $user->id, 'type' => $type,
                        'batch_key' => $batch, 'channel' => $channel,
                    ], ['cycle_key' => $cycle, 'automatic' => $automatic, 'deadline_at' => $event->order_deadline]);
                    if ($delivery->wasRecentlyCreated || $delivery->status === 'cancelled') {
                        if (! $delivery->wasRecentlyCreated) {
                            $delivery->update(['status' => 'pending', 'last_error' => null]);
                        }
                        SendOrderEventNotification::dispatch($delivery->id)->afterCommit();
                        $count++;
                    }
                }
            }

            return $count;
        });
    }

    public function status(OrderEvent $event, string $type): string
    {
        $query = Delivery::query()->where('order_event_id', $event->id)->where('type', $type);
        $last = (clone $query)->whereNotNull('sent_at')->max('sent_at');
        $planned = $this->plannedAt($event, $type);
        $prefix = $this->prefix($type);
        $status = match (true) {
            (clone $query)->where('status', 'failed')->exists() => __('event-notifications.failed'),
            $event->order_deadline && ! $event->order_deadline->isFuture() => __('event-notifications.expired'),
            $event->locked => __('event-notifications.waiting_locked'),
            (clone $query)->where('status', 'pending')->exists() => __('event-notifications.pending'),
            ! $event->{$prefix.'_enabled'} => __('event-notifications.disabled'),
            $type === 'deadline' && ! $event->order_deadline => __('event-notifications.needs_deadline'),
            default => __('event-notifications.ready'),
        };

        return __('event-notifications.planned').': '.($planned ? ApplicationTime::local($planned)->format('d.m.Y H:i') : '—')
            .' · '.__('event-notifications.last_sent').': '.($last ? ApplicationTime::local($last)->format('d.m.Y H:i') : '—')
            .' · '.$status;
    }
}
