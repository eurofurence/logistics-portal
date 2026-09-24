<?php

namespace App\Http\Middleware;

use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetDefaultLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $previousLocale = app()->getLocale();
        app()->setLocale(app(GeneralSettings::class)->default_locale);

        try {
            return $next($request);
        } finally {
            app()->setLocale($previousLocale);
        }
    }
}
