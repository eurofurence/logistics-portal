<?php

return [

    'resources' => [
        'PermissionResource' => \Althinect\FilamentSpatieRolesPermissions\Resources\PermissionResource::class,
        'RoleResource' => \Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource::class,
    ],

    'preload_roles' => true,

    'preload_permissions' => true,

    'navigation_section_group' => 'filament-spatie-roles-permissions::filament-spatie.section.roles_and_permissions', // Default uses language constant

    'team_model' => \App\Models\Team::class,

    'scope_to_tenant' => true,

    /*
     * Set as false to remove from navigation.
     */
    'should_register_on_navigation' => [
        'permissions' => true,
        'roles' => true,
    ],

    'should_show_permissions_for_roles' => true,

    /*
     * Set as true to use simple modal resource.
     */
    'should_use_simple_modal_resource' => [
        'permissions' => false,
        'roles' => false,
    ],

    /*
     * Set as true to remove empty state actions.
     */
    'should_remove_empty_state_actions' => [
        'permissions' => false,
        'roles' => false,
    ],

    /**
     * Set to true to redirect to the resource index instead of the view
     */
    'should_redirect_to_index' => [
        'permissions' => [
            'after_create' => false,
            'after_edit' => false
        ],
        'roles' => [
            'after_create' => false,
            'after_edit' => false
        ],
    ],

    /**
     * Set to true to display relation managers in the resources
     */
    'should_display_relation_managers' => [
        'permissions' => true,
        'users' => false,
        'roles' => true,
    ],

    /*
     * If you want to place the Resource in a Cluster, then set the required Cluster class.
     * Eg. \App\Filament\Clusters\Cluster::class
     */
    'clusters' => [
        'permissions' => null,
        'roles' => null,
    ],

    'guard_names' => [
        'web' => 'web',
    ],

    'toggleable_guard_names' => [
        'roles' => [
            'isToggledHiddenByDefault' => true,
        ],
        'permissions' => [
            'isToggledHiddenByDefault' => true,
        ],
    ],

    'default_guard_name' => null,

    // if false guard option will not be show on screen. You should set a default_guard_name in this case
    'should_show_guard' => true,

    'model_filter_key' => 'return \'%\'.$value;', // Eg: 'return \'%\'.$key.'\%\';'

    'user_name_column' => 'name',

    /*
     * If user_name_column is an accessor from a model, then list columns to search.
     * Default: null, will search by user_name_column
     *
     * Example:
     *
     * 'user_name_searchable_columns' => ['first_name', 'last_name']
     *
     * and in your model:
     *
     * public function getFullNameAttribute() {
     *    return $this->first_name . ' ' . $this->last_name;
     * }
     *
     */
    'user_name_searchable_columns' => 'name',

    /*
     * Icons to use for navigation
     */
    'icons' => [
        'role_navigation' => 'heroicon-o-tag',
        'permission_navigation' => 'heroicon-o-key',
    ],

    /*
     *  Navigation items order - int value, false  restores the default position
     */

    'sort' => [
        'role_navigation' => false,
        'permission_navigation' => false
    ],

    'generator' => [

        'guard_names' => [
            'web',
        ],

        'permission_affixes' => [

            /*
             * Permissions Aligned with Policies.
             * DO NOT change the keys unless the genericPolicy.stub is published and altered accordingly
             */
            'viewAnyPermission' => 'view-any',
            'viewPermission' => 'view',
            'createPermission' => 'create',
            'updatePermission' => 'update',
            'deletePermission' => 'delete',
            //'deleteAnyPermission' => 'delete-any',
            'replicatePermission' => 'replicate',
            'restorePermission' => 'restore',
            //'restoreAnyPermission' => 'restore-any',
            //'reorderPermission' => 'reorder',
            'forceDeletePermission' => 'force-delete',
            //'forceDeleteAnyPermission' => 'force-delete-any',
        ],

        /*
         * returns the "name" for the permission.
         *
         * $permission which is an iteration of [permission_affixes] ,
         * $model The model to which the $permission will be concatenated
         *
         * Eg: 'permission_name' => 'return $permissionAffix . ' ' . Str::kebab($modelName),
         *
         * Note: If you are changing the "permission_name" , It's recommended to run with --clean to avoid duplications
         */
        'permission_name' => 'return $permissionAffix . "-" . $modelName;',

        /*
         * Permissions will be generated for the models associated with the respective Filament Resources
         */
        'discover_models_through_filament_resources' => false,

        /*
         * Include directories which consists of models.
         */
        'model_directories' => [
            app_path('Models'),
            //app_path('Domains/Forum')
        ],

        /*
         * Define custom_models
         */
        'custom_models' => [
            //
        ],

        /*
         * Define excluded_models
         */
        'excluded_models' => [
             //\App\Models\ItemsOperationSite::class

        ],

        'excluded_policy_models' => [
            \App\Models\User::class,
        ],

        /*
         * Define any other permission that should be synced with the DB
         */
        'custom_permissions' => [
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
            'can-see-all_items',
        ],

        'user_model' => \App\Models\User::class,

        'policies_namespace' => 'App\Policies',
    ],
];
