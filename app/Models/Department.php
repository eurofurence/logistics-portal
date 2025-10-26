<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use App\Models\ItemsOperationSite;
use App\Models\InventorySubCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $icon
 * @property string|null $idp_group_id
 * @property int $added_by
 * @property int $edited_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, DepartmentMember> $members
 * @property-read int|null $members_count
 * @property-read Collection<int, Order> $orders
 * @property-read int|null $orders_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @method static DepartmentFactory factory($count = null, $state = [])
 * @method static Builder<static>|Department newModelQuery()
 * @method static Builder<static>|Department newQuery()
 * @method static Builder<static>|Department onlyTrashed()
 * @method static Builder<static>|Department query()
 * @method static Builder<static>|Department whereAddedBy($value)
 * @method static Builder<static>|Department whereCreatedAt($value)
 * @method static Builder<static>|Department whereDeletedAt($value)
 * @method static Builder<static>|Department whereEditedBy($value)
 * @method static Builder<static>|Department whereIcon($value)
 * @method static Builder<static>|Department whereId($value)
 * @method static Builder<static>|Department whereIdpGroupId($value)
 * @method static Builder<static>|Department whereName($value)
 * @method static Builder<static>|Department whereUpdatedAt($value)
 * @method static Builder<static>|Department withTrashed()
 * @method static Builder<static>|Department withoutTrashed()
 * @property-read Collection<int, InventorySubCategory> $inventory_sub_categories
 * @property-read int|null $inventory_sub_categories_count
 * @property-read Collection<int, ItemsOperationSite> $items_operation_sites
 * @property-read int|null $items_operation_sites_count
 * @property-read Collection<int, Storage> $storages
 * @property-read int|null $storages_count
 * @mixin \Eloquent
 */
class Department extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Returns current department members
     *
     * @return HasMany The `members()` function is returning a relationship of type `HasMany` for the `DepartmentMember`
     * model. This indicates that the `Department` model has a one-to-many relationship with the `DepartmentMember` model,
     * where a department can have multiple members.
     */
    public function members(): HasMany
    {
        return $this->hasMany(DepartmentMember::class);
    }

    protected $fillable = [
        'name',
        'idp_group_id',
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

    /**
     * The orders function returns a collection of orders associated with a specific department.
     *
     * @return hasMany
     */
    public function orders(): hasMany
    {
        return $this->hasMany(Order::class, 'department_id');
    }

    /**
     * The roles that belong to the department.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'department_user', 'department_id', 'role_id');
    }

    /**
     * The storages that belong to the department.
     */
    public function storages(): MorphToMany
    {
        return $this->morphToMany(Storage::class, 'storage', 'storage_department', 'storage', 'department');
    }

    /**
     * The operations sites that belong to the department.
     */
    public function items_operation_sites(): HasMany
    {
        return $this->hasMany(ItemsOperationSite::class, 'department');
    }

    /**
     * The operations sites that belong to the department.
     */
    public function inventory_sub_categories(): HasMany
    {
        return $this->hasMany(InventorySubCategory::class, 'department');
    }
}
