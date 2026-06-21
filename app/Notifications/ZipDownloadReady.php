<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ZipDownloadReady extends Notification
{
    use Queueable;

    public function __construct(public string $storagePath) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Wir signieren die URL für Sicherheit
        $downloadUrl = URL::temporarySignedRoute(
            'bills.download-zip', now()->addHours(24), ['path' => $this->storagePath]
        );

        return (new MailMessage)
            ->subject(__('general.zip_download_ready'))
            ->line(__('general.zip_download_ready_message'))
            ->action(__('general.download_zip'), $downloadUrl)
            ->line(__('general.link_valid_24h'));
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => __('general.zip_download_ready'),
            'url' => URL::temporarySignedRoute(
                'bills.download-zip', now()->addHours(24), ['path' => $this->storagePath]
            ),
        ];
    }
}
