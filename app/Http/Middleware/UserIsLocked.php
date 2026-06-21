<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserIsLocked
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            if (Auth::user()->locked == true) {
                return abort(403, __('middleware.account_is_locked'));
            } else {
                return $next($request);
            }
        }

        return $next($request);
    }
}
