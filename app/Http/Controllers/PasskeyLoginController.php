<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Whitelist;
use App\Settings\LoginSettings;
use Filament\Auth\MultiFactor\MultiFactorChallenge;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Http\Controllers\AuthenticateUsingPasskeyController;
use Spatie\LaravelPasskeys\Http\Requests\AuthenticateUsingPasskeysRequest;
use Spatie\LaravelPasskeys\Support\Config;
use Throwable;

class PasskeyLoginController extends AuthenticateUsingPasskeyController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(AuthenticateUsingPasskeysRequest $request): RedirectResponse
    {
        abort_unless(app(LoginSettings::class)->passkeys_enabled, 403);
        $options = $request->session()->pull('passkey-authentication-options');
        $panelId = $request->session()->pull('passkeys.panel');
        $expiresAt = $request->session()->pull('passkeys.expires_at', 0);

        if (! is_string($options) || ! in_array($panelId, ['app', 'admin'], true) || $expiresAt < now()->timestamp) {
            return $this->invalidPasskeyResponse();
        }

        try {
            $passkey = app(Config::getActionClass('find_passkey', FindPasskeyToAuthenticateAction::class))
                ->execute($request->input('start_authentication_response'), $options);
        } catch (Throwable) {
            return $this->invalidPasskeyResponse();
        }

        $user = $passkey?->authenticatable;
        $panel = Filament::getPanel($panelId);
        Filament::setCurrentPanel($panel);

        if (! ($user instanceof User) || $user->locked || ! $user->canAccessPanel($panel)
            || (app(LoginSettings::class)->whitelist_active && ! Whitelist::where('email', $user->email)->exists())) {
            return $this->invalidPasskeyResponse();
        }

        $request->session()->forget('passkeys.redirect');

        if (MultiFactorChallenge::make()->hasEnabledProviders($user)) {
            $request->session()->put('passkeys.pending', [
                'user_id' => $user->id,
                'passkey_id' => $passkey->id,
                'panel' => $panelId,
                'expires_at' => now()->addMinutes(5)->timestamp,
                'remember' => $request->boolean('remember'),
            ]);

            return redirect()->to($panel->getLoginUrl());
        }

        auth($panel->getAuthGuard())->login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $this->firePasskeyEvent($passkey, $request);

        return redirect()->to($panel->getUrl());
    }
}
