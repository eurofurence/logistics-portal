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
 * @property int $storage
 * @property string|null $comment
 * @property int $added_by
 * @property int $edited_by
 * @property int|null $qr_code
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder<static>|StorageArea newModelQuery()
 * @method static Builder<static>|StorageArea newQuery()
 * @method static Builder<static>|StorageArea onlyTrashed()
 * @method static Builder<static>|StorageArea query()
 * @method static Builder<static>|StorageArea whereAddedBy($value)
 * @method static Builder<static>|StorageArea whereComment($value)
 * @method static Builder<static>|StorageArea whereCreatedAt($value)
 * @method static Builder<static>|StorageArea whereDeletedAt($value)
 * @method static Builder<static>|StorageArea whereEditedBy($value)
 * @method static Builder<static>|StorageArea whereId($value)
 * @method static Builder<static>|StorageArea whereName($value)
 * @method static Builder<static>|StorageArea whereQrCode($value)
 * @method static Builder<static>|StorageArea whereStorage($value)
 * @method static Builder<static>|StorageArea whereUpdatedAt($value)
 * @method static Builder<static>|StorageArea withTrashed()
 * @method static Builder<static>|StorageArea withoutTrashed()
 * @mixin \Eloquent
 */
class StorageArea extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'storage', 'comment', 'qr_code', 'added_by', 'edited_by'];

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
