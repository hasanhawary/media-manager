<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Disk
    |--------------------------------------------------------------------------
    |
    | Null means "use filesystems.default" from the host Laravel app.
    |
    */
    'disk' => null,

    /*
    |--------------------------------------------------------------------------
    | Default Storage Options
    |--------------------------------------------------------------------------
    */
    'path' => 'files',
    'visibility' => 'public',
    'fallback_extension' => 'jpg',
    'naming_strategy' => 'uuid',

    /*
    |--------------------------------------------------------------------------
    | Chunk Uploads
    |--------------------------------------------------------------------------
    */
    'chunks' => [
        'directory' => 'chunks',
        'uploads_directory' => 'uploads',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | The chunk-file endpoint is enabled by default at:
    | POST /media-manager/chunk-file
    |
    */
    'routes' => [
        'enabled' => true,
        'prefix' => 'media-manager',
        'middleware' => [],
        'name' => 'media-manager.',
    ],
];
