<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem as ScheduledTaskLogItemModel;

/**
 * @property-read \Spatie\ScheduleMonitor\Models\MonitoredScheduledTask|null $monitoredScheduledTask
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTaskLogItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTaskLogItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTaskLogItem query()
 * @mixin \Eloquent
 */
class ScheduledTaskLogItem extends ScheduledTaskLogItemModel
{
    use HasFactory;
}
