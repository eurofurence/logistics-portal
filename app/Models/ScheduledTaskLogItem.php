<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem as ScheduledTaskLogItemModel;

class ScheduledTaskLogItem extends ScheduledTaskLogItemModel
{
    use HasFactory;
}
