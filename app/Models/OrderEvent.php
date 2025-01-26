<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
