<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property int|null $sub_unit
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \App\Models\User|null $added_by
 * @property \App\Models\User|null $edited_by
 * @property-read \App\Models\SubUnit|null $subUnit
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseUnit onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseUnit whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseUnit whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseUnit whereEditedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseUnit whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseUnit whereSubUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseUnit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseUnit withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseUnit withoutTrashed()
 * @mixin \Eloquent
 */
class BaseUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'base_units';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'sub_unit'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = Auth::user();

            $model->added_by = $user->id;
            $model->edited_by = $user->id;
        });

        static::updating(function ($model) {
            $user = Auth::user();
            $model->edited_by = $user->id;
        });
    }

    public function added_by(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function edited_by(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_by');
    }

    public function subUnit(): HasOne
    {
        return $this->hasOne(SubUnit::class, 'id', 'sub_unit');
    }
}
