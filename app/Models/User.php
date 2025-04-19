<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Panel;
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
     * Get all departments where the user has at least one role.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function departmentsWithRoles()
    {
        return $this->departments()->whereHas('roles', function ($query) {
            $query->where('user_id', $this->id);
        })->get();
    }

    public function departmentMemberships()
    {
        return $this->hasMany(DepartmentMember::class, 'user_id');
    }

    /**
     * Check if the user has a specific permission within a department.
     *
     * @param string $permission The permission to check.
     * @param int $department_id The department ID to check the permission in.
     * @return bool True if the user has the permission, false otherwise.
     */
    public function hasDepartmentRoleWithPermissionTo(string $permission, int $department_id): bool
    {
        return $this->departmentMemberships()
            ->where('department_id', $department_id)
            ->whereHas('role.permissions', function ($query) use ($permission) {
                $query->where('name', $permission)
                    ->where('guard_name', 'web');
            })
            ->exists();
    }

    /**
     * Get all departments where the user has a specific permission.
     *
     * @param string $permission The permission to check.
     * @return array An array of department IDs where the user has the permission.
     */
    public function getDepartmentsWithPermission_Array(string $permission): array
    {
        return $this->departmentMemberships()
            ->whereHas('role.permissions', function ($query) use ($permission) {
                $query->where('name', $permission)
                    ->where('guard_name', 'web');
            })
            ->with('department') // Preloading the department relationship
            ->get()
            ->pluck('department') // Extracting the department models
            ->unique('id') // Removing duplicates based on the department ID
            ->keyBy('id') // Set the array key to the department ID
            ->toArray();
    }

    /**
     * Get the number of departments where the user has a specific permission.
     *
     * @param string $permission The permission to check.
     * @return int The amount of department with that permission
     */
    public function getDepartmentsWithPermission_Count(string $permission): int
    {
        return $this->departmentMemberships()
            ->whereHas('role.permissions', function ($query) use ($permission) {
                $query->where('name', $permission)
                    ->where('guard_name', 'web');
            })
            ->count();
    }

    /**
     * Check if the user has a specific permission in any department.
     *
     * @param string $permission The permission to check.
     * @return bool True if the user has the permission in any department, false otherwise.
     */
    public function hasAnyDepartmentRoleWithPermissionTo(string $permission): bool
    {
        return $this->departmentMemberships()
            ->whereHas('role.permissions', function ($query) use ($permission) {
                $query->where('name', $permission)
                    ->where('guard_name', 'web');
            })
            ->exists();
    }
}
