<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Panel;
use InvalidArgumentException;
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

    public function hasDepartmentPermissionTo(int $department_id, string $permission): bool
    {

    }

    public function getDepartmentsWithPermission(string $permission): array
    {
        
    }
}
