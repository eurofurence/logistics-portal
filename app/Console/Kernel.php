<?php

namespace App\Console;

use Illuminate\Support\Facades\Log;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('schedule-monitor:sync')->everyFifteenMinutes();
        $schedule->command('files:delete-old')->hourly();
        $schedule->command('model:prune', ['--model' => MonitoredScheduledTaskLogItem::class])->daily();

        if(config('app.backup_schedule_active')){
            //Log::info('Running backup schedule...');
            $schedule->command('backup:with-s3')->daily();
            $schedule->command('backup:clean')->daily();
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    protected $commands = [
        // ..
    ];

    protected function bootstrappers()
    {
        return array_merge(
            parent::bootstrappers(),
        );
    }
}
