<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

test('creates every legacy custom permission through migrations on a fresh database', function () {
    $expectedPermissions = [
        'access-adminpanel',
        'access-role-navigation',
        'access-permissions-navigation',
        'access-user-navigation',
        'access-whitelist-navigation',
        'access-healthchecks',
        'can-always-order',
        'can-always-edit-orders',
        'can-always-delete-orders',
        'can-always-see-orders',
        'can-choose-all-departments',
        'can-change-order-status',
        'instant-delivery-notification',
        'can-moderate-order-request',
        'can-always-edit-orderRequests',
        'can-always-delete-orderRequests',
        'can-always-create-orderRequests',
        'can-use-special-order-export',
        'can-use-special-order-functions',
        'can-edit-order-files',
        'can-see-order-files-tab',
        'can-use-article-directory-special-functions',
        'can-change-bill-status',
        'can-reject-bills-button',
        'can-mark-bills-as-finished-button',
        'can-place-order',
        'order-needs-approval',
        'access-backups',
        'can-always-edit-bills',
        'can-always-delete-bills',
        'view-Horizon',
        'can-bulk-sync-order-articles',
        'can-manage-order-relationships',
        'can-bulk-change-order-article-deadlines',
        'can-edit-all-orderRequests',
        'can-create-orderRequests-for-other-departments',
        'can-delete-orderRequests-for-other-departments',
        'can-see-all-orderRequests',
        'can-create-orders-for-other-departments',
        'can-delete-orders-for-other-departments',
        'can-approve-orders-for-other-departments',
        'can-approve-orders',
        'can-always-approve-orders',
        'can-decline-orders-for-other-departments',
        'can-decline-orders',
        'can-always-decline-orders',
        'can-edit-all-orders',
        'can-change-amount-order-table',
        'can-change-amount-order-table-all',
        'can-see-all-orders',
        'bulk-restore-OrderRequest',
        'bulk-delete-OrderRequest',
        'bulk-restore-Order',
        'bulk-delete-Order',
        'can-view-order-delivery-address',
        'can-create-storages-for-all-departments',
        'can-create-global-storages',
        'can-see-all-storages',
        'can-create-items-for-other-departments',
        'can-see-all-items',
        'can-edit-all-bills',
        'can-delete-all-bills',
        'can-restore-all-bills',
        'can-create-bills-for-other-departments',
        'can-see-all-bills',
        'get-new-bill-accountant-notification',
        'get-bill-payment-reminder-accountant-notification',
    ];

    $permissions = Permission::where('guard_name', 'web')->pluck('name')->all();

    expect(array_diff($expectedPermissions, $permissions))->toBe([]);
});

test('adds missing permissions without replacing existing grants or other guards', function () {
    $permission = Permission::findByName('access-adminpanel', 'web');
    $otherGuardPermission = Permission::findOrCreate('access-adminpanel', 'api');
    $role = Role::factory()->create();
    $role->givePermissionTo($permission);
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->givePermissionTo($permission);
    Permission::findByName('can-see-all-bills', 'web')->delete();
    app(PermissionRegistrar::class)->getPermissions();
    $migration = require base_path('database/migrations/2026_09_16_135913_migrate_custom_permissions_to_database.php');

    $migration->up();
    $migration->up();

    expect(Permission::where('name', 'access-adminpanel')->where('guard_name', 'web')->sole()->id)->toBe($permission->id);
    expect(Permission::where('name', 'access-adminpanel')->where('guard_name', 'api')->sole()->id)->toBe($otherGuardPermission->id);
    expect($role->fresh()->hasPermissionTo('access-adminpanel'))->toBeTrue();
    expect($user->fresh()->hasDirectPermission('access-adminpanel'))->toBeTrue();
    expect($user->fresh()->hasPermissionTo('can-see-all-bills'))->toBeFalse();
    $this->assertDatabaseHas('permissions', ['name' => 'can-see-all-bills', 'guard_name' => 'web']);
});

test('retains existing role and user grants when the migration is rolled back', function () {
    $permission = Permission::findByName('access-adminpanel', 'web');
    $role = Role::factory()->create();
    $role->givePermissionTo($permission);
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->givePermissionTo($permission);
    $migration = require base_path('database/migrations/2026_09_16_135913_migrate_custom_permissions_to_database.php');

    $migration->down();

    $this->assertModelExists($permission);
    expect($role->fresh()->hasPermissionTo('access-adminpanel'))->toBeTrue();
    expect($user->fresh()->hasDirectPermission('access-adminpanel'))->toBeTrue();
});
