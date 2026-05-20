<?php

use JaapMoolenaar\UnsplashMcp\Registrar\DiskRegistrar;
use JaapMoolenaar\UnsplashMcp\Registrar\StatamicRegistrar;

return [
    'access_key' => env('UNSPLASH_ACCESS_KEY'),

    'registrar' => env('UNSPLASH_REGISTRAR', 'disk'),
    'registrars' => [
        'disk' => DiskRegistrar::class,
        'statamic' => StatamicRegistrar::class,
    ],

    'temp_disk' => env('UNSPLASH_TEMP_DISK', 'local'),

    'disk' => [
        'name' => env('UNSPLASH_DISK', 'public'),
    ],

    'statamic' => [
        'asset_container' => env('UNSPLASH_STATAMIC_CONTAINER', 'assets'),
    ],
];
