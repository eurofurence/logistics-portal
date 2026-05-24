<?php

namespace App\Models;

use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, SpatiePermission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 * @method static PermissionFactory factory($count = null, $state = [])
 * @method static Builder<static>|Permission newModelQuery()
 * @method static Builder<static>|Permission newQuery()
 * @method static Builder<static>|Permission permission($permissions, $without = false)
 * @method static Builder<static>|Permission query()
 * @method static Builder<static>|Permission role($roles, $guard = null, $without = false)
 * @method static Builder<static>|Permission whereCreatedAt($value)
 * @method static Builder<static>|Permission whereGuardName($value)
 * @method static Builder<static>|Permission whereId($value)
 * @method static Builder<static>|Permission whereName($value)
 * @method static Builder<static>|Permission whereUpdatedAt($value)
 * @method static Builder<static>|Permission withoutPermission($permissions)
 * @method static Builder<static>|Permission withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 */
class Permission extends SpatiePermission
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::updated(function () {
            app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        static::created(function() {
            app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        static::deleted(function() {
            app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }
}
