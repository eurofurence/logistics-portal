<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Auth\MultiFactor\MultiFactorChallenge;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequiredTwoFactorAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if ($user instanceof User && $user->requiresTwoFactorAuthentication()
            && ! MultiFactorChallenge::make()->hasEnabledProviders($user)) {
            return redirect()->guest(Filament::getSetUpRequiredMultiFactorAuthenticationUrl());
        }

        return $next($request);
    }
}
