<?php

namespace App\Actions;

use App\Filament\App\Resources\OrderEvents\Pages\EditOrderEvent;
use App\Models\OrderEvent;
use App\Notifications\OrderEventNotification;
use App\Services\OrderEventNotifications;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification as NotificationSender;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderEventNotificationActions
{
    public static function send(string $type, bool $test = false): Action
    {
        return Action::make($type.($test ? 'Test' : 'Send'))
            ->label(__('event-notifications.'.($test ? 'send_test' : 'send_now')))
            ->icon($test ? 'heroicon-o-beaker' : 'heroicon-o-paper-airplane')
            ->color($test ? 'gray' : 'primary')
            ->visible(fn (?OrderEvent $record): bool => $record !== null && Gate::allows('update', $record)
                && (! $test || Gate::allows('sendTestEmail', $record)))
            ->requiresConfirmation()
            ->modalDescription(__('event-notifications.'.($test ? 'test_confirmation' : 'send_confirmation')))
            ->action(function (OrderEvent $record, EditOrderEvent $livewire, Action $action) use ($type, $test): void {
                Gate::authorize('update', $record);
                if ($test) {
                    Gate::authorize('sendTestEmail', $record);
                }
                $livewire->assertNotificationsSaved();
                $record->refresh();
                if ($type === 'deadline' && ! $record->order_deadline) {
                    throw ValidationException::withMessages(['data.order_deadline' => __('event-notifications.needs_deadline')]);
                }
                try {
                    if ($test) {
                        NotificationSender::sendNow(auth()->user(), new OrderEventNotification($record, $type, true), ['mail']);
                    } else {
                        app(OrderEventNotifications::class)->enqueue($record, $type, false);
                    }
                } catch (ValidationException $exception) {
                    Notification::make()->title($exception->validator->errors()->first())->danger()->send();
                    $action->halt();
                } catch (Throwable $exception) {
                    report($exception);
                    Notification::make()->title(__('event-notifications.send_failed'))->danger()->send();
                    $action->halt();
                }
                Notification::make()->title(__('event-notifications.'.($test ? 'test_sent' : 'queued')))->success()->send();
            });
    }
}
