<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Models\Whitelist;
use App\Settings\LoginSettings;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\View\View;
use Spatie\LaravelPasskeys\Livewire\PasskeysComponent;

class ManagePasskeys extends PasskeysComponent implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public function currentUser(): User
    {
        $user = Filament::auth()->user();
        $panel = Filament::getCurrentOrDefaultPanel();
        $settings = app(LoginSettings::class);

        abort_unless($settings->passkeys_enabled && $user instanceof User
            && ! $user->locked && $user->canAccessPanel($panel)
            && (! $settings->whitelist_active || Whitelist::where('email', $user->email)->exists()), 403);

        return $user;
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('passkeys::passkeys.delete'))
            ->color('danger')
            ->requiresConfirmation()
            ->action(fn (array $arguments) => $this->deletePasskey($arguments['passkey']));
    }

    public function deletePasskey(int|string $passkeyId): void
    {
        parent::deletePasskey($passkeyId);
        $this->currentUser()->unsetRelation('passkeys');
        Notification::make()->title(__('filament-passkeys::passkeys.deleted_notification_title'))->success()->send();
    }

    public function storePasskey(string $passkey): void
    {
        $this->currentUser();
        parent::storePasskey($passkey);
        $this->currentUser()->unsetRelation('passkeys');
        Notification::make()->title(__('filament-passkeys::passkeys.created_notification_title'))->success()->send();
    }

    public function render(): View
    {
        return view('components.passkey-manager', ['passkeys' => $this->currentUser()->passkeys]);
    }
}
