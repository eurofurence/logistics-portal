<?php

namespace App\Notifications;

use App\Filament\App\Resources\ClubMembers\ClubMemberResource;
use App\Models\ClubMember;
use App\Models\User;
use App\Models\Whitelist;
use App\Settings\LoginSettings;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClubMemberProfileChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param list<string> $fields */
    public function __construct(public int $memberId, public array $fields)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return array<string, string> */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        if (! $notifiable instanceof User || $notifiable->locked || ! $notifiable->canAccessPanel(Filament::getPanel('app'))) {
            return false;
        }

        $member = ClubMember::query()->find($this->memberId);

        return $member !== null
            && $notifiable->can('viewAny', ClubMember::class)
            && $notifiable->can('update', $member)
            && (! app(LoginSettings::class)->whitelist_active || Whitelist::query()->where('email', $notifiable->email)->exists());
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('member-portal.notification_title'))
            ->line($this->message())
            ->action(__('member-portal.open_member'), $this->url());
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('member-portal.notification_title'))
            ->body($this->message())
            ->icon('heroicon-o-identification')
            ->actions([Action::make('view')->label(__('member-portal.open_member'))->url($this->url())->button()->markAsRead()])
            ->getDatabaseMessage();
    }

    private function message(): string
    {
        return __('member-portal.notification_body', [
            'member' => (string) $this->memberId,
            'fields' => implode(', ', array_map(fn (string $field): string => __('members.'.$field), $this->fields)),
        ]);
    }

    private function url(): string
    {
        return ClubMemberResource::getUrl('view', ['record' => $this->memberId], panel: 'app');
    }
}
