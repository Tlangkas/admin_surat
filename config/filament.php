<?php

declare(strict_types=1);

return [
    'default_filesystem_disk' => env('FILESYSTEM_DISK', 'local'),
    'broadcasting' => env('REVERB_ENABLED', false) ? [
        'echo' => [
            'broadcaster' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'cluster' => env('REVERB_APP_CLUSTER'),
            'wsHost' => env('REVERB_HOST', 'localhost'),
            'wsPort' => env('REVERB_PORT', 8080),
            'wssPort' => env('REVERB_PORT', 8080),
            'forceTLS' => false,
            'disableStats' => true,
        ],
    ] : [],
];
