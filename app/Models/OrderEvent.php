<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property int $locked
 * @property \Illuminate\Support\Carbon|null $order_deadline
 * @property int $is_active
 * @property int $added_by
 * @property int $edited_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> $orders
 * @property-read int|null $orders_count
 * @method static \Database\Factories\OrderEventFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent whereEditedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent whereLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent whereOrderDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEvent withoutTrashed()
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
