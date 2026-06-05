<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Export Namespace
    |--------------------------------------------------------------------------
    |
    | Namespace used to resolve export classes dynamically.
    | Example: page=user => App\Tools\Export\UserExport
    |
    */
    'namespace' => 'App\\Tools\\Export',

    /*
    |--------------------------------------------------------------------------
    | Translation Settings
    |--------------------------------------------------------------------------
    |
    | The file used to resolve column heading translations in BaseExport.
    | The project's 'lang/en/api.php' already contains common column names
    | (id, name, email, phone, gender, is_active, created_at, etc.),
    | so we point to it to avoid duplicating translation entries.
    |
    */
    'trans_file' => 'api',

    /*
    |--------------------------------------------------------------------------
    | PDF Settings
    |--------------------------------------------------------------------------
    |
    | Static settings can be defined here.
    | Dynamic settings can be resolved using settings_resolver.
    |
    */
    'pdf' => [
        'settings' => [
            // 'logo_url' => null,
            // 'company_name' => null,
        ],

        /*
        |--------------------------------------------------------------------------
        | Settings Resolver
        |--------------------------------------------------------------------------
        |
        | Supported:
        | - Closure
        | - Invokable class string
        | - [ClassName::class, 'method']
        |
        | The resolver must return array.
        |
        */
        'settings_resolver' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Export Module
    |--------------------------------------------------------------------------
    |
    | The Modules/Export module owns all export routing in this project.
    | The package's built-in routes are disabled to avoid duplicate endpoints.
    | ExportBuilder is used directly from the module's controllers.
    |
    */
    'module' => [
        // Disable package module entirely — Modules/Export handles routing & storage
        'enabled' => false,

        'routes' => [
            'enabled' => false,
        ],

        'controllers' => [
            'direct' => Modules\Export\App\Http\Controllers\ExportController::class,
            'jobs'   => Modules\Export\app\Http\Controllers\ExportJobController::class,
        ],

        'services' => [
            'export'      => HasanHawary\ExportBuilder\Services\ExportService::class,
            'export_file' => HasanHawary\ExportBuilder\Services\ExportFileService::class,
            'permissions' => HasanHawary\ExportBuilder\Services\ExportPermissionResolver::class,
        ],

        'storage' => [
            'disk' => 'local',
            'path' => 'exports',
        ],

        'permissions' => [
            'enabled' => false,
            'abilities' => [
                'export' => 'export',
                'queue' => 'create-export-file',
                'view_all' => 'view-all-export-file',
                'view_own' => 'view-own-export-file',
                'delete' => 'delete-export-file',
            ],
            'pages' => [],
        ],
    ],
];
