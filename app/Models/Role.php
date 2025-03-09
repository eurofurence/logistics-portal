<?php

namespace App\Models;

use Filament\Notifications\Notification;
use Spatie\Permission\Models\Role as ModelsRole;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Role extends ModelsRole
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::updated(function () {
            Notification::make()
                ->title(__('general.new_instant_delivery'))
                ->send();
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        });

        static::created(function () {
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        });

        static::deleted(function () {
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'model', 'model_has_roles', 'role_id', 'model_id');
    }

    public function departmentUsers(): HasMany
    {
        return $this->hasMany(DepartmentMember::class, 'role_id');
    }
}
