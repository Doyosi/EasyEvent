<?php

return [
    'table' => 'easy_events',

    'routes' => [
        'web' => [
            'enabled' => true,
            'prefix'  => 'events',
            'name'    => 'easy-events.',
            'middleware' => ['web'],
        ],
        'panel' => [
            'enabled' => true,
            'prefix'  => 'panel/easy-events',
            'name'    => 'panel.easy-events.',
            'middleware' => ['web', 'auth'], // adjust as needed
        ],
    ],

    'pagination' => 15,

    'date_format' => 'Y-m-d H:i',

    'status' => ['draft', 'published', 'archived'],

    // Default event types (you can customize or extend in your app)
    'types' => ['meeting', 'holiday', 'webinar', 'workshop', 'custom'],
];
