<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Database\Factories\OrderEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order_deadline' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

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
