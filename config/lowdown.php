<?php

return [
    'gists' => [
        'cached' => env('LOWDOWN_GISTS_CACHED', true),
        'enabled' => env('LOWDOWN_GISTS_ENABLED', false),
        'token' => env('LOWDOWN_GISTS_TOKEN'),
        'username' => env('LOWDOWN_GISTS_USERNAME'),
    ],
    'sources' => env('LOWDOWN_SOURCES'),
    'dest' => env('LOWDOWN_DEST'),
    'whitelist' => env('LOWDOWN_WHITELIST'),
];
