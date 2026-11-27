<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Wishlist;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WishlistShared extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param list<int> $departmentIds */
    public function __construct(
        public int $wishlistId,
        public string $wishlistName,
        public string $sharedByName,
        public bool $personal,
        public array $departmentIds = [],
    ) {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->personal ? ['database', 'mail'] : ['database'];
    }

    /** @return array<string, string> */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        if (! $notifiable instanceof User) {
            return false;
        }
        $wishlist = Wishlist::find($this->wishlistId);
        if (! $wishlist) {
            return false;
        }
        if ($this->personal) {
            return $wishlist->sharedUsers()->whereKey($notifiable->id)->exists();
        }
        if (! $notifiable->can('view', $wishlist)) {
            return false;
        }

        $departmentIds = $wishlist->sharedDepartments()->whereIn('departments.id', $this->departmentIds)
            ->whereIn('departments.id', $notifiable->departments()->select('departments.id'))->pluck('departments.id');

        foreach ($departmentIds as $departmentId) {
            if ($notifiable->isSuperAdmin()
                || $notifiable->hasDepartmentRoleWithPermissionTo('can-place-order', $departmentId)
                || ($notifiable->can('can-create-orders-for-other-departments') && $notifiable->hasAnyDepartmentRoleWithPermissionTo('can-place-order'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('wishlists.shared_notification_title'))
            ->line($this->message())
            ->action(__('wishlists.open_list'), $this->url());
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('wishlists.shared_notification_title'))
            ->body($this->message(escapeNames: true))
            ->icon('heroicon-o-user-group')
            ->actions([Action::make('view')->label(__('wishlists.open_list'))->url($this->url())->button()->markAsRead()])
            ->getDatabaseMessage();
    }

    protected function message(bool $escapeNames = false): string
    {
        return __('wishlists.shared_notification_body', [
            'person' => $escapeNames ? e($this->sharedByName) : $this->sharedByName,
            'list' => $escapeNames ? e($this->wishlistName) : $this->wishlistName,
        ]);
    }

    protected function url(): string
    {
        return route('filament.app.resources.wishlists.index', ['list' => $this->wishlistId]);
    }
}
