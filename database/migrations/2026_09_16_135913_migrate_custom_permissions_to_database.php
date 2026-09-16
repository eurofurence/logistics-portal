<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
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
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Retain permissions on rollback because existing role and user grants
     * cannot be distinguished from permissions first created by this migration.
     */
    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
