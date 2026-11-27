<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Symfony\Component\HttpFoundation\Cookie;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    protected function newCookie(mixed $request, mixed $config): Cookie
    {
        $cookie = parent::newCookie($request, $config);

        if (! $request->routeIs('member-portal.*')) {
            return $cookie;
        }

        return new Cookie(
            'MEMBER-XSRF-TOKEN',
            $cookie->getValue(),
            $cookie->getExpiresTime(),
            StartWebSession::memberCookiePath($request),
            $cookie->getDomain(),
            $cookie->isSecure(),
            $cookie->isHttpOnly(),
            $cookie->isRaw(),
            $cookie->getSameSite(),
            $cookie->isPartitioned(),
        );
    }
}
