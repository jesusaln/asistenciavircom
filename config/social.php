<?php

return [
    'meta' => [
        'graph_version' => env('META_GRAPH_VERSION', 'v21.0'),
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'page_id' => env('META_PAGE_ID'),
        'page_access_token' => env('META_PAGE_ACCESS_TOKEN', env('META_ACCESS_TOKEN')),
        'instagram_user_id' => env('META_INSTAGRAM_USER_ID'),
    ],
];
