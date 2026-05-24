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
 * @property int|null $storage_area
 * @property int $type
 * @property int|null $qr_code
 * @property int $home_storage
 * @property int|null $parent_container
 * @property string|null $comment
 * @property int $added_by
 * @property Carbon|null $deleted_at
 * @property int $edited_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder<static>|StorageContainer newModelQuery()
 * @method static Builder<static>|StorageContainer newQuery()
 * @method static Builder<static>|StorageContainer onlyTrashed()
 * @method static Builder<static>|StorageContainer query()
 * @method static Builder<static>|StorageContainer whereAddedBy($value)
 * @method static Builder<static>|StorageContainer whereComment($value)
 * @method static Builder<static>|StorageContainer whereCreatedAt($value)
 * @method static Builder<static>|StorageContainer whereDeletedAt($value)
 * @method static Builder<static>|StorageContainer whereEditedBy($value)
 * @method static Builder<static>|StorageContainer whereHomeStorage($value)
 * @method static Builder<static>|StorageContainer whereId($value)
 * @method static Builder<static>|StorageContainer whereName($value)
 * @method static Builder<static>|StorageContainer whereParentContainer($value)
 * @method static Builder<static>|StorageContainer whereQrCode($value)
 * @method static Builder<static>|StorageContainer whereStorageArea($value)
 * @method static Builder<static>|StorageContainer whereType($value)
 * @method static Builder<static>|StorageContainer whereUpdatedAt($value)
 * @method static Builder<static>|StorageContainer withTrashed()
 * @method static Builder<static>|StorageContainer withoutTrashed()
 * @mixin \Eloquent
 */
class StorageContainer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'storage_area', 'type', 'qr_code', 'home_storage', 'parent_container', 'comment', 'added_by', 'edited_by'];

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
