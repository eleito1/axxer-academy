<?php

return [
    'provider' => env('VIDEO_STORAGE_PROVIDER', 'hostinger'),

    'storage' => [
        'disk' => env('VIDEO_STORAGE_DISK', 'video_public'),
        'directory' => env('VIDEO_STORAGE_DIRECTORY', ''),
    ],

    'allowed_extensions' => ['mp4', 'mov', 'webm'],
    'allowed_mimetypes' => ['video/mp4', 'video/quicktime', 'video/webm'],
    'max_megabytes' => (int) env('VIDEO_UPLOAD_MAX_MB', 500),
];
