<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Panel;
use InvalidArgumentException;
use App\Enums\DepartmentRoleEnum;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'ex_id',
        'ex_groups',
        'avatar',
        'locked',
        'comment',
        'email_verified_at',
        'last_login',
        'separated_rights',
        'separated_departments'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login' => 'datetime',
        'ex_groups' => 'array',
        'separated_rights' => 'bool',
        'separated_departments' => 'bool',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Master');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        switch ($panel->getId()) {
            case 'app':
                return true;
                break;

            case 'admin':
                return $this->isSuperAdmin() || $this->checkPermissionTo('access-adminpanel');
                break;

            default:
                return false;
                break;
        }
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar;
    }

    /**
     * The departments function establishes a many-to-many relationship between users and departments in PHP.
     *
     * @return BelongsToMany A BelongsToMany relationship between the current model and the Department model is being
     * returned. The relationship is defined using the `belongsToMany` method, specifying the related model
     * `Department::class`, the pivot table name `'department_user'`, the foreign key `'user_id'`, and the related key
     * `'department_id'`.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user', 'user_id', 'department_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            if ($model->user_id === 0) {
                return false;
            }

            if (!$model->password) {
                unset($model->password);
            }
        });

        static::creating(function ($model) {
            if ($model->user_id === 0) {
                return false;
            }
        });

        static::deleting(function ($model) {
            if ($model->user_id === 0) {
                return false;
            }
        });
    }

    /**
     * The `roles` function defines a many-to-many relationship between the current model and the `Role` model using
     * polymorphic relations.
     *
     * @return MorphToMany The `roles()` function is returning a MorphToMany relationship. It is defining a many-to-many
     * polymorphic relationship between the current model and the `Role` model.
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }

    /**
     * Checks if a user has a specific role in a given department.
     *
     * @param int $user_id The ID of the user to check.
     * @param int|null $department_id The ID of the department to check. If null, checks all departments.
     * @param DepartmentRoleEnum $role The role to check for.
     *
     * @return bool Returns true if the user has the specified role in the department, false otherwise.
     */
    public function hasDepartmentRole(int $user_id, ?int $department_id, DepartmentRoleEnum $role): bool
    {
        dd($this->id);
        $query = DepartmentMember::where('user_id', $user_id)
            ->where('role', $role->value);

        if ($department_id !== null) {
            $query->where('department_id', $department_id);
        }

        return $query->exists();
    }

    /**
     * Checks if a user has any of the specified roles in a given department or any department.
     *
     * @param int $user_id The ID of the user to check.
     * @param int|null $department_id The ID of the department to check. If null, checks all departments.
     * @param array $roles The roles to check for.
     *
     * @return bool Returns true if the user has any of the specified roles in the department, false otherwise.
     *
     * @throws InvalidArgumentException If any role in the array is not an instance of DepartmentRoleEnum.
     */
    public function hasDepartmentRoles(int $user_id, ?int $department_id, array $roles): bool
    {
        // Check whether all elements in the array are instances of DepartmentRoleEnum
        foreach ($roles as $role) {
            if (!$role instanceof DepartmentRoleEnum) {
                throw new InvalidArgumentException('All roles must be instances of ' . get_class(DepartmentRoleEnum::NONE()));
            }
        }

        // Check whether the user has one of the roles in the array for the specified department or all departments
        foreach ($roles as $role) {
            $query = DepartmentMember::where('user_id', $user_id)
                ->where('role', $role->value);

            if ($department_id !== null) {
                $query->where('department_id', $department_id);
            }

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves departments associated with the user having a specific role.
     *
     * This function retrieves departments where the user has a specific role, based on the provided DepartmentRoleEnum.
     * It uses a many-to-many relationship defined in the User model to fetch the relevant departments.
     *
     * @param DepartmentRoleEnum $role The role to check for.
     *
     * @return \Illuminate\Database\Eloquent\Collection A collection of Department models where the user has the specified role.
     */
    public function departmentsWithRole(DepartmentRoleEnum $role)
    {
        return $this->belongsToMany(Department::class, 'department_user', 'user_id', 'department_id')
            ->wherePivot('role', $role->value)
            ->get();
    }

    /**
     * Retrieves departments associated with the user having any of the specified roles.
     *
     * This function retrieves departments where the user has any of the roles provided in the array.
     * It uses a many-to-many relationship defined in the User model to fetch the relevant departments.
     *
     * @param array $roles An array of DepartmentRoleEnum roles to check for.
     *
     * @return \Illuminate\Database\Eloquent\Collection A collection of Department models where the user has any of the specified roles.
     */
    public function departmentsWithRoles(array $roles)
    {
        // Ensure all elements in the array are instances of DepartmentRoleEnum
        foreach ($roles as $role) {
            if (!$role instanceof DepartmentRoleEnum) {
                throw new InvalidArgumentException('All roles must be instances of ' . get_class(DepartmentRoleEnum::NONE()));
            }
        }

        // Extract the role values from the DepartmentRoleEnum instances
        $roleValues = array_map(function ($role) {
            return $role->value;
        }, $roles);

        // Retrieve departments where the user has any of the specified roles
        return $this->belongsToMany(Department::class, 'department_user', 'user_id', 'department_id')
            ->whereIn('department_user.role', $roleValues)
            ->get();
    }
}
