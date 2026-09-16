<?php

namespace App\Notifications;

use App\Models\Order;
use App\Settings\GeneralSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderApprovalReminder extends Notification
{
    /**
     * @param  Collection<int, Order>  $orders
     */
    public function __construct(public Collection $orders) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('general.order_approval_reminder', ['count' => $this->orders->count()], 'en'))
            ->view('emails.order-approval-reminder', [
                'orders' => $this->orders,
                'username' => $notifiable->name,
                'siteName' => app(GeneralSettings::class)->displayName(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('general.order_approval_reminder', ['count' => $this->orders->count()]))
            ->body(__('general.order_approval_reminder_body'))
            ->icon('heroicon-o-shield-exclamation')
            ->actions([
                Action::make('view')
                    ->label(__('general.show'))
                    ->url(route('filament.app.resources.orders.index', ['activeTab' => 'other']))
                    ->button(),
            ])
            ->getDatabaseMessage();
    }
}
