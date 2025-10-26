<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property string|null $contact_details
 * @property string $country
 * @property string $street
 * @property string $city
 * @property string $post_code
 * @property string|null $comment
 * @property string|null $documents
 * @property int $added_by
 * @property int $edited_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder<static>|Storage newModelQuery()
 * @method static Builder<static>|Storage newQuery()
 * @method static Builder<static>|Storage onlyTrashed()
 * @method static Builder<static>|Storage query()
 * @method static Builder<static>|Storage whereAddedBy($value)
 * @method static Builder<static>|Storage whereCity($value)
 * @method static Builder<static>|Storage whereComment($value)
 * @method static Builder<static>|Storage whereContactDetails($value)
 * @method static Builder<static>|Storage whereCountry($value)
 * @method static Builder<static>|Storage whereCreatedAt($value)
 * @method static Builder<static>|Storage whereDeletedAt($value)
 * @method static Builder<static>|Storage whereDocuments($value)
 * @method static Builder<static>|Storage whereEditedBy($value)
 * @method static Builder<static>|Storage whereId($value)
 * @method static Builder<static>|Storage whereName($value)
 * @method static Builder<static>|Storage wherePostCode($value)
 * @method static Builder<static>|Storage whereStreet($value)
 * @method static Builder<static>|Storage whereUpdatedAt($value)
 * @method static Builder<static>|Storage withTrashed()
 * @method static Builder<static>|Storage withoutTrashed()
 * @property int $type
 * @property Department|null $managing_department
 * @property-read Collection<int, StorageDepartmentAccess> $departments
 * @property-read int|null $departments_count
 * @method static Builder<static>|Storage whereManagingDepartment($value)
 * @method static Builder<static>|Storage whereType($value)
 * @mixin \Eloquent
 */
class Storage extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'storages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'contact_details', 'country', 'street', 'city', 'post_code', 'added_by', 'edited_by', 'comment', 'managing_department', 'type', 'owner', 'borrowed_item', 'rented_item', 'will_be_brought_to_next_event'];

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

    /**
     * The departments that belong to the storage.
     */
    public function departments(): HasMany
    {
        return $this->hasMany(StorageDepartmentAccess::class, 'storage', 'department');
    }

    /**
     * The managing department of the storage.
     */
    public function managing_department(): HasOne
    {
        return $this->hasOne(Department::class, 'id', 'managing_department');
    }
}
