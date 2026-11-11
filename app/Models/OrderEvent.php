<?php

namespace App\Models;

use App\Services\LinkedRecordDeletionGuard;
use Database\Factories\OrderEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @property int $id
 * @property string $name
 * @property int $locked
 * @property Carbon|null $order_deadline
 * @property int $is_active
 * @property int $added_by
 * @property int $edited_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Order> $orders
 * @property-read int|null $orders_count
 *
 * @method static OrderEventFactory factory($count = null, $state = [])
 * @method static Builder<static>|OrderEvent newModelQuery()
 * @method static Builder<static>|OrderEvent newQuery()
 * @method static Builder<static>|OrderEvent onlyTrashed()
 * @method static Builder<static>|OrderEvent query()
 * @method static Builder<static>|OrderEvent whereAddedBy($value)
 * @method static Builder<static>|OrderEvent whereCreatedAt($value)
 * @method static Builder<static>|OrderEvent whereDeletedAt($value)
 * @method static Builder<static>|OrderEvent whereEditedBy($value)
 * @method static Builder<static>|OrderEvent whereId($value)
 * @method static Builder<static>|OrderEvent whereIsActive($value)
 * @method static Builder<static>|OrderEvent whereLocked($value)
 * @method static Builder<static>|OrderEvent whereName($value)
 * @method static Builder<static>|OrderEvent whereOrderDeadline($value)
 * @method static Builder<static>|OrderEvent whereUpdatedAt($value)
 * @method static Builder<static>|OrderEvent withTrashed()
 * @method static Builder<static>|OrderEvent withoutTrashed()
 *
 * @mixin \Eloquent
 */
class OrderEvent extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'locked',
        'order_deadline',
        'added_by',
        'edited_by',
        'is_active',
        'deadline_reminder_enabled', 'deadline_reminder_mode', 'deadline_reminder_days',
        'deadline_reminder_at', 'deadline_reminder_channels', 'opening_notification_enabled',
        'opening_notification_mode', 'opening_notification_at', 'opening_notification_channels',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order_deadline' => 'datetime',
        'deadline_reminder_enabled' => 'boolean',
        'deadline_reminder_days' => 'integer',
        'opening_notification_enabled' => 'boolean',
        'deadline_reminder_at' => 'datetime',
        'opening_notification_at' => 'datetime',
        'deadline_reminder_channels' => 'array',
        'opening_notification_channels' => 'array',
    ];

    protected $attributes = [
        'deadline_reminder_enabled' => true,
        'deadline_reminder_mode' => 'relative',
        'deadline_reminder_days' => 7,
        'deadline_reminder_channels' => '["mail","database"]',
        'opening_notification_enabled' => false,
        'opening_notification_mode' => 'manual',
        'opening_notification_channels' => '["mail","database"]',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(fn (OrderEvent $event): bool => ! app(LinkedRecordDeletionGuard::class)->hasLinks($event));

        static::creating(function ($model) {
            $model->added_by = Auth::user()->id;
            $model->edited_by = Auth::user()->id;
        });

        static::updating(function ($model) {
            $model->edited_by = Auth::user()->id;
        });

        static::saving(function ($orderEvent) {
            if ($orderEvent->is_active) {
                OrderEvent::query()->where('id', '!=', $orderEvent->id)->update(['is_active' => 0]);
            }
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'order_event_id');
    }

    public function departments()
    {
        return Department::whereHas('orders', function ($query) {
            $query->where('order_event_id', $this->id);
        })->get();
    }

    public function is_active(): bool
    {
        return $this->is_active();
    }
}
