<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    public function rootView(Request $request): string
    {
        return $request->routeIs('member-portal.*') ? 'member-portal' : parent::rootView($request);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $previousLocale = app()->getLocale();
        $locale = $request->session()->get('member_portal_locale');

        if ($request->routeIs('member-portal.*') && is_string($locale) && array_key_exists($locale, config('app.available_locales'))) {
            app()->setLocale($locale);
        }

        try {
            $response = parent::handle($request, $next);

            if ($request->routeIs('member-portal.*')) {
                $response->headers->set('Cache-Control', 'private, no-store');
                $response->headers->set('Referrer-Policy', 'same-origin');
            }

            return $response;
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if ($request->routeIs('member-portal.*')) {
            return [
                ...parent::share($request),
                'ui' => __('member-portal.ui'),
                'locale' => app()->getLocale(),
                'locales' => config('app.available_locales'),
                'fields' => __('members'),
                'logo' => route('branding.member-login-logo', absolute: false),
                'status' => fn (): ?string => $request->session()->get('member_portal_status'),
                'links' => [
                    'locale' => route('member-portal.locale.update', absolute: false),
                    'login' => route('member-portal.login', absolute: false),
                    'authenticate' => route('member-portal.authenticate', absolute: false),
                    'forgot' => route('member-portal.request-reset', absolute: false),
                    'activation' => route('member-portal.activation.redeem', absolute: false),
                    'reset' => route('member-portal.reset.redeem', absolute: false),
                    'email-change' => route('member-portal.email-change.redeem', absolute: false),
                    'profile' => route('member-portal.profile.update', absolute: false),
                    'email' => route('member-portal.email.update', absolute: false),
                    'password' => route('member-portal.password.update', absolute: false),
                    'logout' => route('member-portal.logout', absolute: false),
                ],
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
        ];
    }
}
