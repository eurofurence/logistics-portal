<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperUser
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(Auth::check()){
            if(!$request->user()->isSuperAdmin()){
                return abort(403, __('middleware.superuser_needed'));
            } else {
                return $next($request);
            }
        }

        return $next($request);
    }
}
