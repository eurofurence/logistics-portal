<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $ex_id
 * @property array<array-key, mixed>|null $ex_groups
 * @property string|null $avatar
 * @property int $locked
 * @property string|null $comment
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $last_login
 * @property bool $separated_rights
 * @property bool $separated_departments
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Collection<int, \App\Models\DepartmentMember> $departmentMemberships
 * @property-read int|null $department_memberships_count
 * @property-read Collection<int, \App\Models\Department> $departments
 * @property-read int|null $departments_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereExGroups($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereExId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSeparatedDepartments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSeparatedRights($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 * @mixin \Eloquent
 */

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
        'separated_departments',
        'notification_email'
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
     * Returns the notification_email, or if null, the email.
     */
    public function getNotificationEmailOrFallbackAttribute($notification): string
    {
        return $this->notification_email ?? $this->email;
    }

    public function routeNotificationForMail($notification): string
    {
        return $this->getNotificationEmailOrFallbackAttribute($notification);
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
    public function departmentsWithRoles(): Collection
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
     * Get all departments where the user has a specific role.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDepartmentsWithPermission(string $permission)
    {
        return $this->departmentMemberships()
            ->whereHas('role.permissions', function ($query) use ($permission) {
                $query->where('name', $permission)
                    ->where('guard_name', 'web');
            })
            ->with('department')
            ->get()
            ->pluck('department');
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

    /**
     * Get all roles of a specific user in a specific department.
     *
     * @param int $department_id The department ID to get roles from.
     * @param int|null $user_id The optional user ID to filter roles. If null, the current user's ID is used.
     * @return array An array of roles in the specified department for the specified user.
     */
    public function getRolesInDepartment(int $department_id, ?int $user_id = null): array
    {
        $user_id = $user_id ?? $this->id; // Use the current user's ID if no user_id is provided

        return $this->departmentMemberships()
            ->where('department_id', $department_id)
            ->where('user_id', $user_id)
            ->with('role') // Preloading the role relationship
            ->get()
            ->pluck('role') // Extracting the role models
            ->unique('id') // Removing duplicates based on the role ID
            ->keyBy('id') // Set the array key to the role ID
            ->toArray();
    }
}
