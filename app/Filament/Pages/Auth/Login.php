<?php

namespace App\Filament\Pages\Auth;

use App\Models\User; // Zurück zum originalen Schema aus deinem Projekt
use App\Models\Whitelist;
use App\Settings\LoginSettings;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Spatie\LaravelPasskeys\Events\PasskeyUsedToAuthenticateEvent;
use Spatie\LaravelPasskeys\Http\Requests\AuthenticateUsingPasskeysRequest;
use Spatie\LaravelPasskeys\Models\Passkey;

class Login extends \Filament\Auth\Pages\Login
{
    public function mount(): void
    {
        parent::mount();

        if (session()->has('passkeys.pending')) {
            $passkey = $this->getPendingPasskey();
            $user = $passkey->authenticatable;
            $this->userUndertakingMultiFactorAuthentication = encrypt($user->getAuthIdentifier());
            $this->getMultiFactorChallenge()->beforeChallenge($user);
            $this->multiFactorChallengeForm->fill();
        }
    }

    public function authenticate(): ?LoginResponse
    {
        if (! session()->has('passkeys.pending')) {
            return parent::authenticate();
        }

        $passkey = $this->getPendingPasskey();
        $user = $passkey->authenticatable;

        if ($this->getMultiFactorChallenge()->hasEnabledProviders($user)) {
            if ($this->isMultiFactorChallengeRateLimited($user)) {
                return null;
            }

            $this->multiFactorChallengeForm->validate();
        }

        $passkey = $this->getPendingPasskey();
        $pending = session()->pull('passkeys.pending');
        Filament::auth()->login($passkey->authenticatable, $pending['remember']);
        session()->regenerate();
        event(new PasskeyUsedToAuthenticateEvent($passkey, AuthenticateUsingPasskeysRequest::createFrom(request())));

        return app(LoginResponse::class);
    }

    protected function getPendingPasskey(): Passkey
    {
        $pending = session('passkeys.pending');
        $settings = app(LoginSettings::class);
        $panel = Filament::getCurrentOrDefaultPanel();
        $passkey = is_array($pending) ? Passkey::find($pending['passkey_id']) : null;
        $user = $passkey?->authenticatable;

        if (! $settings->passkeys_enabled || ! ($user instanceof User)
            || $user->id !== ($pending['user_id'] ?? null)
            || ($pending['panel'] ?? null) !== $panel->getId()
            || ($pending['expires_at'] ?? 0) < now()->timestamp
            || $user->locked || ! $user->canAccessPanel($panel)
            || ($settings->whitelist_active && ! Whitelist::where('email', $user->email)->exists())) {
            session()->forget('passkeys.pending');
            $this->throwFailureValidationException();
        }

        return $passkey;
    }

    public function form(Schema $schema): Schema
    {
        return parent::form($schema);
    }

    protected function getFormActions(): array
    {
        $actions = parent::getFormActions();

        if (config('app.identity_mode')) {
            $actions[] = Action::make('sso_login')
                ->url('/app/oauth/identity')
                ->label('EF Identity')
                ->icon('heroicon-o-cursor-arrow-rays')
                ->color('gray');
        }

        return $actions;
    }
}
