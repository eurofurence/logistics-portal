<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Whitelist;
use Illuminate\Http\Request;
use App\Settings\LoginSettings;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckWhitelist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Try to retrieve the whitelist_active property
            $whitelistActive = app(LoginSettings::class)->whitelist_active;
        } catch (\Exception $e) {
            // Set default value if an error occurs
            $whitelistActive = true;
        }

        if ($whitelistActive) {
            if (Auth::check()) {
                $existsInWhitelist = Whitelist::where('email', Auth::user()->email)->exists();

                if (!$existsInWhitelist) {
                    return abort(403, __('middleware.not_on_whitelist'));
                }
            }
        }

        return $next($request);
    }
}
