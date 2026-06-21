<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\MonitoredScheduledTaskPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\ScheduledTaskLogItemPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        SpatiePermission::class => PermissionPolicy::class,
        MonitoredScheduledTask::class => MonitoredScheduledTaskPolicy::class,
        MonitoredScheduledTaskLogItem::class => ScheduledTaskLogItemPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->isSuperAdmin() ? true : null;
        });
    }
}
