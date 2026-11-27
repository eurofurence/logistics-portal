<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\PwaConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class DatabaseNotificationPush extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $databaseNotificationId)
    {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function shouldSend(User $notifiable, string $channel): bool
    {
        return ! $notifiable->locked
            && filled(config('webpush.vapid.public_key'))
            && filled(config('webpush.vapid.private_key'))
            && $notifiable->notifications()->whereKey($this->databaseNotificationId)->exists();
    }

    public function toWebPush(User $notifiable): WebPushMessage
    {
        $data = $notifiable->notifications()->findOrFail($this->databaseNotificationId)->data;

        return (new WebPushMessage)
            ->title($this->plainText($data['title'] ?? app(PwaConfiguration::class)->name(), 150))
            ->body($this->plainText($data['body'] ?? '', 500))
            ->icon('/images/pwa-192.png')
            ->tag('database-'.$this->databaseNotificationId)
            ->data(['url' => route('filament.app.pages.dashboard', absolute: false)]);
    }

    private function plainText(string $value, int $limit): string
    {
        return Str::limit(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'), $limit);
    }
}
