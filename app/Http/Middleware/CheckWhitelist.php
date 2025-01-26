<?php

namespace App\Http\Middleware;

use App\Models\Whitelist;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckWhitelist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $existsInWhitelist = Whitelist::where('email', Auth::user()->email)->exists();

            if (!$existsInWhitelist) {
                return abort(403, __('middleware.not_on_whitelist'));
            }
        }

        return $next($request);
    }
}
