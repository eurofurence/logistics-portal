<?php

namespace App\Providers;

use App\Models\Bill;
use App\Models\Order;
use App\Observers\BillObserver;
use App\Observers\OrderObserver;
use App\Services\AsinDataService;
use Spatie\Health\Facades\Health;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\URL;
use App\Http\Responses\LogoutResponse;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Laravel\Socialite\Contracts\Factory;
use Spatie\Health\Checks\Checks\PingCheck;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\CpuLoadHealthCheck\CpuLoadCheck;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\DatabaseSizeCheck;
//use Spatie\SecurityAdvisoriesHealthCheck\SecurityAdvisoriesCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use App\Providers\Socialite\SocialiteIdentityProvider;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Spatie\SecurityAdvisoriesHealthCheck\SecurityAdvisoriesCheck;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\Filament\Auth\Http\Responses\Contracts\LogoutResponse::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        if ($this->app->runningInConsole()) {
            return;
        }

        FilamentColor::register([
            'danger' => Color::Red,
            'gray' => Color::Zinc,
            'info' => Color::Blue,
            'primary' => Color::Amber,
            'success' => Color::Green,
            'warning' => Color::Amber,
            'delivered' => Color::Teal,
            'received' => Color::Fuchsia,
            'checking' => Color::Orange
        ]);

        Health::checks([
            DebugModeCheck::new(),
            EnvironmentCheck::new(),
            //ScheduleCheck::new(),
            UsedDiskSpaceCheck::new(),
            SecurityAdvisoriesCheck::new(),
            CacheCheck::new(),
            RedisCheck::new(),
            HorizonCheck::new(),
            DatabaseSizeCheck::new()->failWhenSizeAboveGb(errorThresholdGb: 5.0),
            PingCheck::new()->url('https://identity.eurofurence.org/')->name('Identity status'),
            CpuLoadCheck::new(),
            DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan(50)
                ->failWhenMoreConnectionsThan(100),
            //QueueCheck::new(),
        ]);


        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['de', 'en'])
                ->visible(outsidePanels: true)
                ->flags([
                    'de' => asset('images/icons/flags/germany.svg'),
                    'en' => asset('images/icons/flags/american.svg'),
                ])
                ->circular();
        });

        $socialite = $this->app->make(Factory::class);
        $socialite->extend('identity', function () use ($socialite) {
            $config = config('services.identity');

            return $socialite->buildProvider(SocialiteIdentityProvider::class, $config);
        });

        RateLimiter::for('SyncAsinDataToOrderArticle', function (object $job) {
            $rate_limit = (new AsinDataService)->getRateLimitPerMinute();

            return Limit::perMinute($rate_limit);
        });

        // Observers
        Order::observe(OrderObserver::class);
        Bill::observe(BillObserver::class);
    }
}
