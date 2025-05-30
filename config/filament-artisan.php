<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Middleware list for web routes
    |--------------------------------------------------------------------------
    |
    | You can pass any middleware for routes, by default it's just [web] group
    | of middleware.
    |
    */
    'middlewares' => [
        'web',
        'auth',
        'super_user',
        'system_admin'
    ],

    /*
    |--------------------------------------------------------------------------
    | Route prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for gui routes. By default url is [/~artisan-gui].
    | For your wish you can set it for example 'my-'. So url will be [/my-artisan-gui].
    |
    | Why tilda? It's selected for prevent route names correlation.
    |
    */
    'prefix' => 'admin/',

    /*
    |--------------------------------------------------------------------------
    | Home url
    |--------------------------------------------------------------------------
    |
    | Where to go when [home] button is pressed
    |
    */
    'home' => '/',

    /*
    |--------------------------------------------------------------------------
    | Only on local
    |--------------------------------------------------------------------------
    |
    | Flag that preventing showing commands if environment is on production
    |
    */
    'local' => false,

    /*
    |--------------------------------------------------------------------------
    | Developer gate
    |--------------------------------------------------------------------------
    |
    | Flag to disable or enable developer gate
    |
    */
    'developer_gate' => true,

    /*
    |--------------------------------------------------------------------------
    | List of command permissions
    |--------------------------------------------------------------------------
    |
    | Specify permissions to every single command. Can be a string or array
    | of permissions
    |
    | Example:
    |   'make:controller' => 'create-controller',
    |   'make:event' => ['generate-files', 'create-event'],
    |
    */
    'permissions' => [],

    /*
    |--------------------------------------------------------------------------
    | List of commands
    |--------------------------------------------------------------------------
    |
    | List of all default commands that has end of execution. Commands like
    | [serve] not supported in case of server side behavior of php.
    | Keys means group. You can shuffle commands as you wish and add your own.
    |
    */
    'commands' => [
        'filament' => [
            'filament:cache-components',
            'filament:check-translations',
            'filament:clear-cached-components',
        ],
        'icons' => [
            'icons:cache',
            'icons:clear'
        ],
        'laravel' => [
            'schedule:run',
        ],
        'optimize' => [
            'optimize',
            'optimize:clear',
        ],
        'cache' => [
            'cache:clear',
            'cache:forget',
            'config:clear',
            'config:cache',
        ],
        'migrate' => [
            'migrate',
            'migrate:status',
        ],
        'queue' => [
            'queue:clear',
            'queue:failed',
            'queue:flush',
            'queue:forget',
            'queue:restart',
            'queue:retry',
            'queue:retry-batch',
        ],
        'route' => [
            'route:cache',
            'route:clear',
            'route:list',
        ],
        'view' => [
            'view:cache',
            'view:clear'
        ],
        'permission' => [
            'permissions:sync',
            'permission:cache-reset'
        ],
        'backups' => [
            'backup:with-s3',
            'backup:clean'
        ],
        'schedule-monitor' => [
            'schedule-monitor:sync'
        ],
        's3' => [
            's3:download-logs',
            's3:delete-logs'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | Specify settings for the navigation.
    |
    |   show-only-commands-showing:
    |       if set true, hide in the navigation if the commands are not shown.
    |   group:
    |       set the group name for the navigation (will be translate).
    */
    'navigation' => [
        'show-only-commands-showing' => true,
        'group' => 'DEV',
        'icon' => 'heroicon-o-command-line',
    ]
];
