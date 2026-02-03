<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SystemAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(Auth::check()){
            if(!$request->user()->value('email') == config('app.admin_mail')){
                return abort(403);
            } else {
                return $next($request);
            }
        } else {
            return abort(403);
        }

        return $next($request);
    }
}
