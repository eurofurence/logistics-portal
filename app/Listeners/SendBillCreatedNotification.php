<?php

namespace App\Listeners;

use App\Models\User;
use App\Events\BillCreated;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\GeneralNotification;
use Filament\Notifications\Actions\Action;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class SendBillCreatedNotification implements ShouldQueue
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
    public function handle(BillCreated $event): void
    {
        $users_to_notify = User::permission('get-new-bill-accountant-notification')->get();

            if (!empty($users_to_notify)) {
                $model_link = null;
                $model_link = route('filament.app.resources.bills.view', $event->bill);

                foreach ($users_to_notify as $user_to_notify) {
                    //Send email
                    Notification::send($user_to_notify, new GeneralNotification(username: $user_to_notify->name, subject: __('general.bill', [], 'en') . ' #' . $event->bill->id . ' - ' . $event->bill->title, titel: __('general.new_bill_is_available', [], 'en'), message: __('general.new_bill_is_available', [], 'en'), details_title: $event->bill->title, details_title_hint: null, details_message: __('general.department') . ': ' . $event->bill->connected_department->name, details_link: $model_link, details_link_title: __('general.show', [], 'en')));

                    //Send database notification
                    FilamentNotification::make()
                        ->title(__('general.bill'))
                        ->body(__('general.new_bill_is_available') . ': ' . $event->bill->title)
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
                        ->sendToDatabase($user_to_notify);
                }
            }
    }
}
