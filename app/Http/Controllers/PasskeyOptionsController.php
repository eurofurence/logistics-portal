<?php

namespace App\Http\Controllers;

use App\Settings\LoginSettings;
use Illuminate\Http\Request;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction;
use Spatie\LaravelPasskeys\Support\Config;

class PasskeyOptionsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): string
    {
        abort_unless(app(LoginSettings::class)->passkeys_enabled, 403);
        $data = $request->validate(['panel' => ['required', 'in:app,admin']]);
        $request->session()->forget('passkeys.pending');
        $request->session()->put('passkeys.panel', $data['panel']);
        $request->session()->put('passkeys.expires_at', now()->addMinutes(5)->timestamp);

        return Config::getAction('generate_passkey_authentication_options', GeneratePasskeyAuthenticationOptionsAction::class)->execute();
    }
}
