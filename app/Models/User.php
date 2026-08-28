<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Permission as AppPermission;
use App\Models\Role as AppRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $ex_id
 * @property array<array-key, mixed>|null $ex_groups
 * @property string|null $avatar
 * @property int $locked
 * @property string|null $comment
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $last_login
 * @property bool $separated_rights
 * @property bool $separated_departments
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, DepartmentMember> $departmentMemberships
 * @property-read int|null $department_memberships_count
 * @property-read Collection<int, Department> $departments
 * @property-read int|null $departments_count
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, AppRole> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User onlyTrashed()
 * @method static Builder<static>|User permission($permissions, $without = false)
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static Builder<static>|User whereAvatar($value)
 * @method static Builder<static>|User whereComment($value)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereDeletedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereExGroups($value)
 * @method static Builder<static>|User whereExId($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereLastLogin($value)
 * @method static Builder<static>|User whereLocked($value)
 * @method static Builder<static>|User whereName($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereSeparatedDepartments($value)
 * @method static Builder<static>|User whereSeparatedRights($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @method static Builder<static>|User withTrashed()
 * @method static Builder<static>|User withoutPermission($permissions)
 * @method static Builder<static>|User withoutRole($roles, $guard = null)
 * @method static Builder<static>|User withoutTrashed()
 *
 * @property-read string $notification_email_or_fallback
 *
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
        'notification_email',
        'discord_webhook',
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
     *                       returned. The relationship is defined using the `belongsToMany` method, specifying the related model
     *                       `Department::class`, the pivot table name `'department_user'`, the foreign key `'user_id'`, and the related key
     *                       `'department_id'`.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user', 'user_id', 'department_id');
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function sharedWishlists(): BelongsToMany
    {
        return $this->belongsToMany(Wishlist::class, 'wishlist_user');
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            if ($model->user_id === 0) {
                return false;
            }

            if (! $model->password) {
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

    public function routeNotificationForWebhook()
    {
        return $this->discord_webhook;
    }

    /**
     * The `roles` function defines a many-to-many relationship between the current model and the `Role` model using
     * polymorphic relations.
     *
     * @return MorphToMany The `roles()` function is returning a MorphToMany relationship. It is defining a many-to-many
     *                     polymorphic relationship between the current model and the `Role` model.
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(AppRole::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }

    /**
     * Get all departments where the user has at least one role.
     */
    public function departmentsWithRoles(): Collection
    {
        return $this->departments()->whereHas('roles', function ($query) {
            $query->where('user_id', $this->id);
        })->get();
    }

    public function departmentMemberships(): User|Builder|HasMany
    {
        return $this->hasMany(DepartmentMember::class, 'user_id');
    }

    public function getPermissionCacheVersion(): int
    {
        return Cache::remember("user_{$this->id}_permission_version", now()->addDays(7), fn () => 1);
    }

    public function incrementPermissionCacheVersion(): void
    {
        Cache::put("user_{$this->id}_permission_version", $this->getPermissionCacheVersion() + 1, now()->addDays(7));
    }

    public static function invalidateDepartmentPermissionsForRole(AppRole $role): void
    {
        self::whereHas('departmentMemberships', function ($query) use ($role) {
            $query->where('role_id', $role->id);
        })->each(function (User $user) {
            $user->incrementPermissionCacheVersion();
        });
    }

    public static function invalidateDepartmentPermissionsForPermission(AppPermission $permission): void
    {
        self::whereHas('departmentMemberships.role.permissions', function ($query) use ($permission) {
            $query->where('id', $permission->id);
        })->each(function (User $user) {
            $user->incrementPermissionCacheVersion();
        });
    }

    /**
     * Check if the user has a specific permission within a department.
     *
     * @param  string  $permission  The permission to check.
     * @param  int  $department_id  The department ID to check the permission in.
     * @return bool True if the user has the permission, false otherwise.
     */
    public function hasDepartmentRoleWithPermissionTo(string $permission, int $department_id): bool
    {
        $version = $this->getPermissionCacheVersion();
        $cacheKey = "user_{$this->id}_v{$version}_dept_{$department_id}_perm_{$permission}";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($permission, $department_id) {
            return $this->departmentMemberships()
                ->where('department_id', $department_id)
                ->whereHas('role.permissions', function ($query) use ($permission) {
                    $query->where('name', $permission)
                        ->where('guard_name', 'web');
                })
                ->exists();
        });
    }

    /**
     * Get all departments where the user has a specific permission.
     *
     * @param  string  $permission  The permission to check.
     * @return array An array of department IDs where the user has the permission.
     */
    public function getDepartmentsWithPermission_Array(string $permission): array
    {
        $version = $this->getPermissionCacheVersion();
        $cacheKey = "user_{$this->id}_v{$version}_perms_{$permission}_dept_array";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($permission) {
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
        });
    }

    /**
     * Get all departments where the user has a specific permission.
     */
    public function getDepartmentsWithPermission(string $permission): \Illuminate\Support\Collection
    {
        $version = $this->getPermissionCacheVersion();
        $cacheKey = "user_{$this->id}_v{$version}_perms_{$permission}_dept_coll";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($permission) {
            return $this->departmentMemberships()
                ->whereHas('role.permissions', function ($query) use ($permission) {
                    $query->where('name', $permission)
                        ->where('guard_name', 'web');
                })
                ->with('department')
                ->get()
                ->pluck('department');
        });
    }

    /**
     * Get the number of departments where the user has a specific permission.
     *
     * @param  string  $permission  The permission to check.
     * @return int The amount of department with that permission
     */
    public function getDepartmentsWithPermission_Count(string $permission): int
    {
        $version = $this->getPermissionCacheVersion();
        $cacheKey = "user_{$this->id}_v{$version}_perms_{$permission}_dept_count";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($permission) {
            return $this->departmentMemberships()
                ->whereHas('role.permissions', function ($query) use ($permission) {
                    $query->where('name', $permission)
                        ->where('guard_name', 'web');
                })
                ->count();
        });
    }

    /**
     * Check if the user has a specific permission in any department.
     *
     * @param  string  $permission  The permission to check.
     * @return bool True if the user has the permission in any department, false otherwise.
     */
    public function hasAnyDepartmentRoleWithPermissionTo(string $permission): bool
    {
        $version = $this->getPermissionCacheVersion();
        $cacheKey = "user_{$this->id}_v{$version}_perms_{$permission}_any";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($permission) {
            return $this->departmentMemberships()
                ->whereHas('role.permissions', function ($query) use ($permission) {
                    $query->where('name', $permission)
                        ->where('guard_name', 'web');
                })
                ->exists();
        });
    }

    /**
     * Get all roles of a specific user in a specific department.
     *
     * @param  int  $department_id  The department ID to get roles from.
     * @param  int|null  $user_id  The optional user ID to filter roles. If null, the current user's ID is used.
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
