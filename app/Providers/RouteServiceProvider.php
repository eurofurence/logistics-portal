<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/app';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('member-portal-login', fn (Request $request): array => [
            Limit::perMinute(30)->by($request->ip()),
            Limit::perMinute(5)->by($this->loginRateLimitKey($request).'|'.$request->ip()),
        ]);
        RateLimiter::for('member-portal-reset', fn (Request $request): array => [
            Limit::perHour(20)->by($request->ip()),
            Limit::perHour(5)->by($this->loginRateLimitKey($request)),
        ]);
        RateLimiter::for('member-portal-token', fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('member-portal-security', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    private function loginRateLimitKey(Request $request): string
    {
        $loginId = $request->input('login_id');

        return is_string($loginId) ? hash('sha256', Str::lower(trim($loginId))) : 'invalid-login-id';
    }
}
