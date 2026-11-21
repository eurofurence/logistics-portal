<?php

namespace App\Http\Middleware;

use App\Models\ClubMemberAccount;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureClubMemberAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $account = Auth::guard(ClubMemberAccount::GUARD)->user();

        if (! $account instanceof ClubMemberAccount) {
            return to_route('member-portal.login');
        }

        if (! $account->member?->canUsePortal()
            || $account->password === null
            || $request->session()->get(ClubMemberAccount::SESSION_VERSION) !== $account->session_version) {
            self::forget($request);

            return to_route('member-portal.login');
        }

        return $next($request);
    }

    public static function forget(Request $request): void
    {
        Auth::guard(ClubMemberAccount::GUARD)->logoutCurrentDevice();
        $request->session()->forget(ClubMemberAccount::SESSION_VERSION);
        $request->session()->regenerate(true);
    }
}
