<?php

namespace App\Models;

use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Spatie\Permission\Models\Role as ModelsRole;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 * @method static RoleFactory factory($count = null, $state = [])
 * @method static Builder<static>|Role newModelQuery()
 * @method static Builder<static>|Role newQuery()
 * @method static Builder<static>|Role permission($permissions, $without = false)
 * @method static Builder<static>|Role query()
 * @method static Builder<static>|Role whereCreatedAt($value)
 * @method static Builder<static>|Role whereGuardName($value)
 * @method static Builder<static>|Role whereId($value)
 * @method static Builder<static>|Role whereName($value)
 * @method static Builder<static>|Role whereUpdatedAt($value)
 * @method static Builder<static>|Role withoutPermission($permissions)
 * @mixin \Eloquent
 */
class Role extends ModelsRole
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            if ($model->id == 1 || $model->name == 'Master') {
                return false;
            }
        });

        static::deleting(function ($model) {
            if ($model->id == 1 || $model->name == 'Master') {
                return false;
            }
        });

        static::updated(function () {
            app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        static::created(function () {
            app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        static::deleted(function () {
            app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'model', 'model_has_roles', 'role_id', 'model_id');
    }
}
