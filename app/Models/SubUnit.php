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
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \App\Models\User|null $added_by
 * @property \App\Models\User|null $edited_by
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubUnit onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubUnit whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubUnit whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubUnit whereEditedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubUnit whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubUnit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubUnit whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubUnit withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubUnit withoutTrashed()
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
