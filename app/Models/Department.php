<?php

namespace App\Models;

use InvalidArgumentException;
use App\Enums\DepartmentRoleEnum;
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
        'idp_group_id'
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
     * Checks if a user has a specific role for a given department.
     *
     * @param User $user The user to check for the role.
     * @param int $department_id The ID of the department to check.
     * @param DepartmentRoleEnum $role The role to check for.
     *
     * @return bool Returns true if the user has the specified role for the given department, otherwise false.
     */
    public static function checkForRole(User $user, int $department_id, DepartmentRoleEnum $role): bool
    {
        return DepartmentMember::where('department_id', $department_id)->where('user_id', $user->id)->where('role', $role->value)->exists();
    }

    /**
     * Checks if a user has any of the specified roles for a given department.
     *
     * @param User $user The user to check for roles.
     * @param int $department_id The ID of the department to check.
     * @param array $roles An array of DepartmentRoleEnum instances representing the roles to check.
     *
     * @return bool Returns true if the user has any of the specified roles for the given department, otherwise false.
     *
     * @throws InvalidArgumentException If any element in the $roles array is not an instance of DepartmentRoleEnum.
     */
    public static function checkForRoles(User $user, int $department_id, array $roles): bool
    {
        // Check whether all elements in the array are instances of DepartmentRoleEnum
        foreach ($roles as $role) {
            if (!$role instanceof DepartmentRoleEnum) {
                throw new InvalidArgumentException('All roles must be instances of ' . get_class(DepartmentRoleEnum::NONE()));
            }
        }

        // Check whether the user has one of the roles in the array for the specified department
        foreach ($roles as $role) {
            if (DepartmentMember::where('department_id', $department_id)
                ->where('user_id', $user->id)
                ->where('role', $role->value)
                ->exists()
            ) {
                return true;
            }
        }

        return false;
    }
}
