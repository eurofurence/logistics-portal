<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as ModelsRole;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property string|null $description
 * @property bool $assignable_in_app
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 *
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
 *
 * @mixin \Eloquent
 */
class Role extends ModelsRole
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['description'];

    protected function casts(): array
    {
        return ['assignable_in_app' => 'boolean'];
    }

    public function isMaster(): bool
    {
        return $this->id === 1 || $this->name === 'Master';
    }

    public function canBeAssignedInApp(): bool
    {
        return ! $this->isMaster() && $this->guard_name === 'web' && $this->assignable_in_app;
    }

    public function localizedDescription(): string
    {
        $translations = array_filter($this->getTranslations('description'), fn (?string $value): bool => filled($value));

        return $translations[app()->getLocale()] ?? $translations[config('app.fallback_locale')] ?? '';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Role $role): void {
            if ($role->isMaster()) {
                $role->assignable_in_app = false;
            }
        });

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

        static::updated(function ($role) {
            app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
            User::invalidateDepartmentPermissionsForRole($role);
        });

        static::created(function ($role) {
            if ($role->isMaster() && $role->assignable_in_app) {
                $role->updateQuietly(['assignable_in_app' => false]);
            }
            app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
            User::invalidateDepartmentPermissionsForRole($role);
        });

        static::deleted(function ($role) {
            app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
            User::invalidateDepartmentPermissionsForRole($role);
        });
    }

    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'model', 'model_has_roles', 'role_id', 'model_id');
    }
}
