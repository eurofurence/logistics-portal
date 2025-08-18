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
 * @property string|null $label
 * @property int $type
 * @property string|null $note
 * @property int $added_by
 * @property int $updated_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder<static>|Code newModelQuery()
 * @method static Builder<static>|Code newQuery()
 * @method static Builder<static>|Code onlyTrashed()
 * @method static Builder<static>|Code query()
 * @method static Builder<static>|Code whereAddedBy($value)
 * @method static Builder<static>|Code whereCreatedAt($value)
 * @method static Builder<static>|Code whereDeletedAt($value)
 * @method static Builder<static>|Code whereId($value)
 * @method static Builder<static>|Code whereLabel($value)
 * @method static Builder<static>|Code whereNote($value)
 * @method static Builder<static>|Code whereType($value)
 * @method static Builder<static>|Code whereUpdatedAt($value)
 * @method static Builder<static>|Code whereUpdatedBy($value)
 * @method static Builder<static>|Code withTrashed()
 * @method static Builder<static>|Code withoutTrashed()
 * @mixin \Eloquent
 */
class Code extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'label',
        'type',
        'note',
        'added_by',
        'updated_by'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->added_by = Auth::user()->id;
            $model->updated_by = Auth::user()->id;
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::user()->id;
        });
    }
}
