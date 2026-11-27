<?php

namespace App\Notifications;

use App\Models\OrderEvent;
use App\Services\ApplicationTime;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderEventNotification extends Notification
{
    public function __construct(public OrderEvent $event, public string $type, public bool $test = false) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function title(): string
    {
        return ($this->test ? '[TEST] ' : '').__('event-notifications.'.$this->type.'_subject', ['event' => $this->event->name]);
    }

    public function body(): string
    {
        return __('event-notifications.'.$this->type.'_body', [
            'event' => $this->event->name,
            'deadline' => ApplicationTime::local($this->event->order_deadline)?->format('d.m.Y H:i T') ?? '—',
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new GeneralNotification(
            username: $notifiable->name,
            subject: $this->title(),
            titel: $this->title(),
            message: $this->body(),
            details_link: route('filament.app.resources.order-articles.index'),
            details_link_title: __('event-notifications.catalog'),
        ))->toMail($notifiable);
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()->title(e($this->title()))->body(e($this->body()))
            ->icon('heroicon-o-calendar-days')
            ->actions([Action::make('catalog')->label(__('event-notifications.catalog'))
                ->url(route('filament.app.resources.order-articles.index'))->button()])
            ->getDatabaseMessage();
    }
}
