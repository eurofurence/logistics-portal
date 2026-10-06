<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['access-general-settings', 'access-theme-settings', 'access-login-settings'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Preserve existing role and user grants on rollback.
     */
    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
