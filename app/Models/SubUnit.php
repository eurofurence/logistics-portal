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
 * @property string $value
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User|null $added_by
 * @property User|null $edited_by
 * @method static Builder<static>|SubUnit newModelQuery()
 * @method static Builder<static>|SubUnit newQuery()
 * @method static Builder<static>|SubUnit onlyTrashed()
 * @method static Builder<static>|SubUnit query()
 * @method static Builder<static>|SubUnit whereAddedBy($value)
 * @method static Builder<static>|SubUnit whereCreatedAt($value)
 * @method static Builder<static>|SubUnit whereDeletedAt($value)
 * @method static Builder<static>|SubUnit whereEditedBy($value)
 * @method static Builder<static>|SubUnit whereId($value)
 * @method static Builder<static>|SubUnit whereName($value)
 * @method static Builder<static>|SubUnit whereUpdatedAt($value)
 * @method static Builder<static>|SubUnit whereValue($value)
 * @method static Builder<static>|SubUnit withTrashed()
 * @method static Builder<static>|SubUnit withoutTrashed()
 * @mixin \Eloquent
 */
class SubUnit extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'value'
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
}
