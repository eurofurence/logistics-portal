<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class StartWebSession extends StartSession
{
    public const MEMBER_COOKIE_SUFFIX = '_members';

    public static function memberCookiePath(Request $request): string
    {
        return $request->getBasePath().'/members';
    }

    public function getSession(Request $request): Session
    {
        if (! $request->routeIs('member-portal.*')) {
            return parent::getSession($request);
        }

        $session = $this->manager->driver();
        $session->setId($request->cookies->get($session->getName().self::MEMBER_COOKIE_SUFFIX));

        return $session;
    }

    protected function addCookieToResponse(Response $response, Session $session): void
    {
        $request = request();

        if (! $request->routeIs('member-portal.*')) {
            parent::addCookieToResponse($response, $session);

            return;
        }

        $config = $this->manager->getSessionConfig();

        if ($this->sessionIsPersistent($config)) {
            $response->headers->setCookie(new Cookie(
                $session->getName().self::MEMBER_COOKIE_SUFFIX,
                $session->getId(),
                $this->getCookieExpirationDate(),
                self::memberCookiePath($request),
                $config['domain'],
                $config['secure'],
                $config['http_only'] ?? true,
                false,
                $config['same_site'] ?? null,
                $config['partitioned'] ?? false,
            ));
        }
    }
}
