<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 *
 *
 * @property int $id
 * @property string $name
 * @property string|null $shortname
 * @property string|null $serialnumber
 * @property int|null $weight_g
 * @property int|null $stackable
 * @property int|null $unit
 * @property string|null $due_date
 * @property string|null $sorted_out
 * @property string|null $description
 * @property string|null $comment
 * @property int $department
 * @property int $added_by
 * @property int $edited_by
 * @property int|null $price
 * @property int $locked
 * @property int|null $specific_editor
 * @property string|null $buy_date
 * @property int|null $qr_code
 * @property int|null $storage_container_id
 * @property int $dangerous_good
 * @property int $big_size
 * @property int $needs_truck
 * @property string|null $url
 * @property int|null $storage
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $addedBy
 * @property-read \App\Models\Department|null $department_
 * @property-read \App\Models\User|null $editedBy
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereBigSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereBuyDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereDangerousGood($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereEditedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereNeedsTruck($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereQrCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereSerialnumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereShortname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereSortedOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereSpecificEditor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereStackable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereStorage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereStorageContainerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereWeightG($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item withoutTrashed()
 * @mixin \Eloquent
 */
class Item extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array'
     */
    protected $fillable = ['name', 'shortname', 'serialnumber', 'weight_g', 'stackable', 'unit', 'due_date', 'sorted_out', 'description', 'comment', 'department', 'edited_by', 'added_by', 'price', 'locked', 'specific_editor', 'buy_date', 'qr_code', 'storage_container_id', 'storage', 'owner', 'borrowed_item', 'rented_item', 'will_be_brought_to_next_event'];

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

    public function department_(): HasOne
    {
        return $this->hasOne(Department::class, 'id', 'department');
    }

    public function addedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function editedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_by');
    }

    public function storage(): HasOne
    {
        return $this->hasOne(Storage::class, 'id', 'storage');
    }
}
