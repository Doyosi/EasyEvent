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
            'middleware' => ['web', 'auth'],
        ],
        'api' => [
            'enabled' => true,
            'prefix'  => 'api/easy-events',
            'name'    => 'easy-events.api.',
            'middleware' => ['api'], // add auth:sanctum here if needed
            'paginate_default' => 0, // 0 returns a plain array by default
            'per_page' => 15,
            'max_limit' => 100,
        ],
    ],

    'pagination' => 15,

    'date_format' => 'Y-m-d H:i',

    'status' => ['draft', 'published', 'archived'],

    // Default event types (you can customize or extend in your app)
    'types' => ['meeting', 'holiday', 'webinar', 'workshop', 'custom'],
];
