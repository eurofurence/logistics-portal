<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\User;
use App\Policies\PermissionPolicy;
use Illuminate\Support\Facades\Gate;
//use App\Policies\RouteStatisticPolicy;
use App\Policies\ScheduledTaskLogItemPolicy;
use App\Policies\MonitoredScheduledTaskPolicy;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
//use Bilfeldt\LaravelRouteStatistics\Models\RouteStatistic;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        SpatiePermission::class => PermissionPolicy::class,
        //RouteStatistic::class => RouteStatisticPolicy::class,
        MonitoredScheduledTask::class => MonitoredScheduledTaskPolicy::class,
        MonitoredScheduledTaskLogItem::class => ScheduledTaskLogItemPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
     public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->isSuperAdmin() ? true: null;
        });
    }
}
