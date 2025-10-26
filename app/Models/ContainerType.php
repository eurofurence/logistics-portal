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
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User|null $added_by
 * @property User|null $edited_by
 * @method static Builder<static>|ContainerType newModelQuery()
 * @method static Builder<static>|ContainerType newQuery()
 * @method static Builder<static>|ContainerType onlyTrashed()
 * @method static Builder<static>|ContainerType query()
 * @method static Builder<static>|ContainerType whereAddedBy($value)
 * @method static Builder<static>|ContainerType whereCreatedAt($value)
 * @method static Builder<static>|ContainerType whereDeletedAt($value)
 * @method static Builder<static>|ContainerType whereEditedBy($value)
 * @method static Builder<static>|ContainerType whereId($value)
 * @method static Builder<static>|ContainerType whereName($value)
 * @method static Builder<static>|ContainerType whereUpdatedAt($value)
 * @method static Builder<static>|ContainerType withTrashed()
 * @method static Builder<static>|ContainerType withoutTrashed()
 * @mixin \Eloquent
 */
class ContainerType extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

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
}
