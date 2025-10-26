<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $added_by
 * @property int $edited_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder<static>|OrderCategory newModelQuery()
 * @method static Builder<static>|OrderCategory newQuery()
 * @method static Builder<static>|OrderCategory onlyTrashed()
 * @method static Builder<static>|OrderCategory query()
 * @method static Builder<static>|OrderCategory whereAddedBy($value)
 * @method static Builder<static>|OrderCategory whereCreatedAt($value)
 * @method static Builder<static>|OrderCategory whereDeletedAt($value)
 * @method static Builder<static>|OrderCategory whereDescription($value)
 * @method static Builder<static>|OrderCategory whereEditedBy($value)
 * @method static Builder<static>|OrderCategory whereId($value)
 * @method static Builder<static>|OrderCategory whereName($value)
 * @method static Builder<static>|OrderCategory whereUpdatedAt($value)
 * @method static Builder<static>|OrderCategory withTrashed()
 * @method static Builder<static>|OrderCategory withoutTrashed()
 * @mixin \Eloquent
 */
class OrderCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'added_by',
        'edited_by'
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
    }
}
