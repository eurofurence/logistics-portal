<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask as ScheduledTaskModel;

/**
 * @property int $id
 * @property string $name
 * @property string|null $type
 * @property string $cron_expression
 * @property string|null $timezone
 * @property string|null $ping_url
 * @property Carbon|null $last_started_at
 * @property Carbon|null $last_finished_at
 * @property Carbon|null $last_failed_at
 * @property Carbon|null $last_skipped_at
 * @property Carbon|null $registered_on_oh_dear_at
 * @property Carbon|null $last_pinged_at
 * @property int $grace_time_in_minutes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, MonitoredScheduledTaskLogItem> $logItems
 * @property-read int|null $log_items_count
 * @method static Builder<static>|MonitoredScheduledTask newModelQuery()
 * @method static Builder<static>|MonitoredScheduledTask newQuery()
 * @method static Builder<static>|MonitoredScheduledTask query()
 * @method static Builder<static>|MonitoredScheduledTask whereCreatedAt($value)
 * @method static Builder<static>|MonitoredScheduledTask whereCronExpression($value)
 * @method static Builder<static>|MonitoredScheduledTask whereGraceTimeInMinutes($value)
 * @method static Builder<static>|MonitoredScheduledTask whereId($value)
 * @method static Builder<static>|MonitoredScheduledTask whereLastFailedAt($value)
 * @method static Builder<static>|MonitoredScheduledTask whereLastFinishedAt($value)
 * @method static Builder<static>|MonitoredScheduledTask whereLastPingedAt($value)
 * @method static Builder<static>|MonitoredScheduledTask whereLastSkippedAt($value)
 * @method static Builder<static>|MonitoredScheduledTask whereLastStartedAt($value)
 * @method static Builder<static>|MonitoredScheduledTask whereName($value)
 * @method static Builder<static>|MonitoredScheduledTask wherePingUrl($value)
 * @method static Builder<static>|MonitoredScheduledTask whereRegisteredOnOhDearAt($value)
 * @method static Builder<static>|MonitoredScheduledTask whereTimezone($value)
 * @method static Builder<static>|MonitoredScheduledTask whereType($value)
 * @method static Builder<static>|MonitoredScheduledTask whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MonitoredScheduledTask extends ScheduledTaskModel
{
    use HasFactory;
}
