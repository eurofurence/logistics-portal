<?php

namespace App\Listeners;

use App\Events\BillStatusChanged;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\GeneralNotification;
use Filament\Notifications\Actions\Action;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class SendBillStatusChangedNotification  implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BillStatusChanged $event): void
    {
        $model_link = null;
        $model_link = route('filament.app.resources.bills.view', $event->bill);

        //Send email
        Notification::send($event->bill->addedBy, new GeneralNotification($event->bill->addedBy->name, __('general.bill', [], 'en') . ' #' . $event->bill->id . ' - ' . $event->bill->title, __('general.status_has_changed', [], 'en'), __('general.status_has_changed_bill', [], 'en'), $event->bill->title, null, null, $model_link, __('general.show', [], 'en')));

        //Send database notification
        FilamentNotification::make()
            ->title(__('general.bill'))
            ->body(__('general.status_has_changed') . ': ' . $event->bill->title)
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->iconColor('info')
            ->actions([
                Action::make(__('general.mark_as_unread'))
                    ->markAsUnread(),
                Action::make(__('general.mark_as_read'))
                    ->markAsRead(),
                Action::make(__('general.show'))
                    ->url(route('filament.app.resources.bills.view', $event->bill))
                    ->button()
            ])
            ->sendToDatabase($event->bill->addedBy);
    }
}
