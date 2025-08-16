<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask as ScheduledTaskModel;

/**
 * @property int $id
 * @property string $name
 * @property string|null $type
 * @property string $cron_expression
 * @property string|null $timezone
 * @property string|null $ping_url
 * @property \Illuminate\Support\Carbon|null $last_started_at
 * @property \Illuminate\Support\Carbon|null $last_finished_at
 * @property \Illuminate\Support\Carbon|null $last_failed_at
 * @property \Illuminate\Support\Carbon|null $last_skipped_at
 * @property \Illuminate\Support\Carbon|null $registered_on_oh_dear_at
 * @property \Illuminate\Support\Carbon|null $last_pinged_at
 * @property int $grace_time_in_minutes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem> $logItems
 * @property-read int|null $log_items_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask whereCronExpression($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask whereGraceTimeInMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask whereLastFailedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask whereLastFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask whereLastPingedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask whereLastSkippedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask whereLastStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask wherePingUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask whereRegisteredOnOhDearAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitoredScheduledTask whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MonitoredScheduledTask extends ScheduledTaskModel
{
    use HasFactory;
}
