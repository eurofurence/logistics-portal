<?php

namespace App\Listeners;

use App\Events\BillStatusChanged;
use App\Notifications\GeneralNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Notification;

class SendBillStatusChangedNotification implements ShouldQueueAfterCommit
{
    /**
     * Handle the event.
     */
    public function handle(BillStatusChanged $event): void
    {
        $modelLink = route('filament.app.resources.bills.view', $event->bill);
        $statusChange = __('general.bill_status_transition', [
            'from' => __('general.'.$event->previousStatus, [], 'en'),
            'to' => __('general.'.$event->newStatus, [], 'en'),
        ], 'en');

        Notification::send($event->bill->addedBy, new GeneralNotification(
            username: $event->bill->addedBy->name,
            subject: __('general.bill', [], 'en').' #'.$event->bill->id.' - '.$event->bill->title,
            titel: __('general.status_has_changed', [], 'en'),
            message: $statusChange,
            details_title: $event->bill->title,
            details_message: $event->hasComment ? __('general.bill_comment_login_notice', [], 'en') : null,
            details_link: $modelLink,
            details_link_title: __('general.show', [], 'en'),
        ));

        // Send database notification
        FilamentNotification::make()
            ->title(__('general.bill'))
            ->body($event->bill->title.': '.__('general.bill_status_transition', [
                'from' => __('general.'.$event->previousStatus),
                'to' => __('general.'.$event->newStatus),
            ]))
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->iconColor('info')
            ->actions([
                Action::make(__('general.mark_as_unread'))
                    ->markAsUnread(),
                Action::make(__('general.mark_as_read'))
                    ->markAsRead(),
                Action::make(__('general.show'))
                    ->url(route('filament.app.resources.bills.view', $event->bill))
                    ->button(),
            ])
            ->sendToDatabase($event->bill->addedBy);
    }
}
