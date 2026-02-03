<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property int|null $sub_unit
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User|null $added_by
 * @property User|null $edited_by
 * @property-read SubUnit|null $subUnit
 * @method static Builder<static>|BaseUnit newModelQuery()
 * @method static Builder<static>|BaseUnit newQuery()
 * @method static Builder<static>|BaseUnit onlyTrashed()
 * @method static Builder<static>|BaseUnit query()
 * @method static Builder<static>|BaseUnit whereAddedBy($value)
 * @method static Builder<static>|BaseUnit whereCreatedAt($value)
 * @method static Builder<static>|BaseUnit whereDeletedAt($value)
 * @method static Builder<static>|BaseUnit whereEditedBy($value)
 * @method static Builder<static>|BaseUnit whereId($value)
 * @method static Builder<static>|BaseUnit whereName($value)
 * @method static Builder<static>|BaseUnit whereSubUnit($value)
 * @method static Builder<static>|BaseUnit whereUpdatedAt($value)
 * @method static Builder<static>|BaseUnit withTrashed()
 * @method static Builder<static>|BaseUnit withoutTrashed()
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
