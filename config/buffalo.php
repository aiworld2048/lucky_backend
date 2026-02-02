<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Site Metadata
    |--------------------------------------------------------------------------
    |
    | These values describe the primary Buffalo site that this application
    | represents. They are used when generating UID/token pairs and can be
    | overridden per-site via the "sites" array below.
    |
    */
    'site' => [
        'name' => env('BUFFALO_SITE_NAME', 'AZM999'),
        'prefix' => env('BUFFALO_SITE_PREFIX', 'az9'),
        'url' => env('BUFFALO_SITE_URL', 'https://master.azm999.com/'), // Must match provider config for token generation
        'lobby_url' => env('BUFFALO_SITE_LOBBY_URL', ''), // Lobby redirect URL // https://online.azm999.com
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional Multi-Site Definitions
    |--------------------------------------------------------------------------
    |
    | Use this section to describe additional site prefixes that should reuse
    | the same Buffalo integration. Each entry can override the default
    | metadata (name, URL, lobby URL, etc.).
    |
    */
    'default_site' => env('BUFFALO_DEFAULT_SITE', 'az9'),
    'sites' => [
        'az9' => [
            'name' => env('BUFFALO_SITE_NAME', 'AZM999'),
            'prefix' => env('BUFFALO_SITE_PREFIX', 'az9'),
            'site_url' => env('BUFFALO_SITE_URL', 'https://master.azm999.com/'), // Must match provider config for token generation
            'lobby_url' => env('BUFFALO_SITE_LOBBY_URL', ''), // Lobby redirect URL https://online.azm999.com
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider (Game Login) API
    |--------------------------------------------------------------------------
    */
    'api' => [
        'url' => env('BUFFALO_GAME_LOGIN_URL', 'https://api-ms3.african-buffalo.club/api/game-login'),
        'domain' => env('BUFFALO_GAME_DOMAIN', 'online.azm999.com'),
        'timeout' => (int) env('BUFFALO_HTTP_TIMEOUT', 30),
        'game_server_url' => env('BUFFALO_GAME_SERVER_URL', 'https://online.azm999.com'),
        'game_id' => (int) env('BUFFALO_DEFAULT_GAME_ID', 23),
    ],

    /*
    |--------------------------------------------------------------------------
    | External Launch Endpoint (Maxwin Myanmar)
    |--------------------------------------------------------------------------
    |
    | This endpoint is invoked when we need to request a launch URL from the
    | upstream provider (https://maxwinmyanmar.pro/api/buffalo/launch-game).
    |
    */
    'provider_launch' => [
        'url' => env('BUFFALO_PROVIDER_LAUNCH_URL', 'https://maxwinmyanmar.pro/api/buffalo/launch-game'),
        'type_id' => (int) env('BUFFALO_DEFAULT_TYPE_ID', 1),
        'provider_id' => (int) env('BUFFALO_DEFAULT_PROVIDER_ID', 23),
        'timeout' => (int) env('BUFFALO_PROVIDER_TIMEOUT', 30),
    ],
];

