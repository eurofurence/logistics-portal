<?php

namespace App\Models;

use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem as ScheduledTaskLogItemModel;

/**
 * @property-read MonitoredScheduledTask|null $monitoredScheduledTask
 * @method static Builder<static>|ScheduledTaskLogItem newModelQuery()
 * @method static Builder<static>|ScheduledTaskLogItem newQuery()
 * @method static Builder<static>|ScheduledTaskLogItem query()
 * @mixin \Eloquent
 */
class ScheduledTaskLogItem extends ScheduledTaskLogItemModel
{
    use HasFactory;
}
