<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use App\Models\InventorySubCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property string|null $shortname
 * @property string|null $serialnumber
 * @property int|null $weight
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
 * @property string|null $owner
 * @property int $borrowed_item
 * @property int $rented_item
 * @property int $will_be_brought_to_next_event
 * @property int|null $operation_site
 * @property array<array-key, mixed>|null $custom_fields
 * @property int|null $sub_category
 * @property string|null $manufacturer_barcode
 * @property-read \App\Models\Department|null $connected_department
 * @property-read \App\Models\ItemsOperationSite|null $connected_operation_site
 * @property-read \App\Models\Storage|null $connected_storage
 * @property-read InventorySubCategory|null $connected_sub_category
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereBorrowedItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereCustomFields($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereManufacturerBarcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereOperationSite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereOwner($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereRentedItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereSubCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereWillBeBroughtToNextEvent($value)
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
    protected $fillable = ['name', 'shortname', 'serialnumber', 'weight', 'stackable', 'unit', 'due_date', 'sorted_out', 'description', 'comment', 'department', 'edited_by', 'added_by', 'price', 'locked', 'specific_editor', 'buy_date', 'qr_code', 'storage_container_id', 'storage', 'owner', 'borrowed_item', 'rented_item', 'will_be_brought_to_next_event', 'operation_site', 'custom_fields', 'sub_category', 'manufacturer_barcode'];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        #TODO: Sollte das Department mal im Nachhinein geändert werden müssen hier Checks eingebaut werden die Prüfen ob die Kategorie/operation site zum Department gehört


        static::creating(function ($model) {
            $model->added_by = Auth::user()->id;
            $model->edited_by = Auth::user()->id;
        });

        static::updating(function ($model) {
            $model->edited_by = Auth::user()->id;
        });
    }

    /**
     * The department that belong to the item.
     */
    public function connected_department(): HasOne
    {
        return $this->hasOne(Department::class, 'id', 'department');
    }

    /**
     * The user that added the item
     */
    public function addedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    /**
     * The last user that edited the item
     */
    public function editedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_by');
    }

    /**
     * The storage that belongs to the item
     */
    public function connected_storage(): HasOne
    {
        return $this->hasOne(Storage::class, 'id', 'storage');
    }

    /**
     * The operation site that belongs to the item
     */
    public function connected_operation_site(): HasOne
    {
        return $this->hasOne(ItemsOperationSite::class, 'id', 'operation_site');
    }

    /**
     * The operation site that belongs to the item
     */
    public function connected_sub_category(): HasOne
    {
        return $this->hasOne(InventorySubCategory::class, 'id', 'sub_category');
    }
}
