<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | These options configures if and how Inertia uses Server Side Rendering
    | to pre-render the initial visits made to your application's pages.
    |
    */

    'ssr' => [
        'enabled' => false,
        'url' => 'http://127.0.0.1:13714',
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | The values described here are used to locate Inertia components on the
    | filesystem. Specifically, when testing your application, these are
    | used to check if page components actually exist on the filesystem.
    |
    */

    'pages' => [
        'paths' => [
            resource_path('js/Pages'),
        ],
        'extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],
    ],

    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [
            resource_path('js/Pages'),
        ],
        'page_extensions' => [
            'js',
            'vue',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | History
    |--------------------------------------------------------------------------
    |
    | If you want to encrypt the history state of your application, you can
    | set this to true. This will encrypt the history state before saving it
    | to the browser's history.
    |
    */

    'history' => [
        'encrypt' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Script Element for Initial Page Data
    |--------------------------------------------------------------------------
    |
    | When enabled, Inertia will render the initial page data using a script
    | tag instead of a data attribute on the root element. This is required
    | for Inertia v2 / v3 and @inertiajs/vue3 ^3.0 compatibility.
    |
    */

    'use_script_element_for_initial_page' => true,
];
