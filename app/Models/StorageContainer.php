<?php

namespace App\Models;

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
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $edited_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer whereEditedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer whereHomeStorage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer whereParentContainer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer whereQrCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer whereStorageArea($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageContainer withoutTrashed()
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
