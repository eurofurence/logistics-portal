<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::updated(function () {
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        });

        static::created(function() {
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        });

        static::deleted(function() {
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }
}
