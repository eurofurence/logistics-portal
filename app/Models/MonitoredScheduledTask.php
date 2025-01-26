<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask as ScheduledTaskModel;

class MonitoredScheduledTask extends ScheduledTaskModel
{
    use HasFactory;
}
