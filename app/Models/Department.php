<?php

namespace App\Models;

use InvalidArgumentException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
     * The function `roles()` returns a HasMany relationship for the Role model with the foreign key `roles_id`.
     *
     * @return HasMany A relationship method `roles()` is being returned, which defines a one-to-many relationship between
     * the current model and the `Role` model. The relationship specifies that the `Role` model is related to the current
     * model through the foreign key `roles_id`.
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'roles_id');
    }
}
