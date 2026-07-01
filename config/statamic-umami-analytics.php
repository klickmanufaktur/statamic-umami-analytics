<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Umami API
    |--------------------------------------------------------------------------
    |
    | Self-hosted Umami usually uses an API URL like https://umami.test/api.
    | Umami Cloud uses https://api.umami.is/v1 and an x-umami-api-key header.
    |
    */

    'api_url' => env('UMAMI_API_URL', env('UMAMI_BASE_URL')),

    // Public Umami web UI (where editors log in). Leave null to derive it from
    // the API URL: api.umami.is -> cloud.umami.is, self-hosted -> URL without /api.
    'dashboard_url' => env('UMAMI_DASHBOARD_URL'),

    // URL of the tracking script embedded via the {{ umami }} tag. Leave null
    // to derive it from the dashboard URL (dashboard_url + /script.js).
    'script_url' => env('UMAMI_SCRIPT_URL'),

    'website_id' => env('UMAMI_WEBSITE_ID'),

    'auth' => env('UMAMI_AUTH', 'auto'),

    'api_key' => env('UMAMI_API_KEY'),

    'api_key_header' => env('UMAMI_API_KEY_HEADER', 'x-umami-api-key'),

    'token' => env('UMAMI_TOKEN'),

    'username' => env('UMAMI_USERNAME'),

    'password' => env('UMAMI_PASSWORD'),

    'auth_token_ttl' => (int) env('UMAMI_AUTH_TOKEN_TTL', 3300),

    'timeout' => (int) env('UMAMI_TIMEOUT', 10),

    'connect_timeout' => (int) env('UMAMI_CONNECT_TIMEOUT', 3),

    'cache_ttl' => (int) env('UMAMI_CACHE_TTL', 300),

    // The "currently active visitors" value is near-realtime, so it gets a much
    // shorter cache window than the rest of the analytics data.
    'active_cache_ttl' => (int) env('UMAMI_ACTIVE_CACHE_TTL', 15),

    'timezone' => env('UMAMI_TIMEZONE', env('APP_TIMEZONE', 'UTC')),

    'periods' => [7, 30, 90, 180, 365],

    'default_period' => 30,

    'cp_nav' => [
        'section' => 'Tools',
        'title' => 'Analytics',
    ],

    'entry_tab' => [
        'enabled' => true,
        'collections' => ['pages'],
        'tab' => 'analytics',
        'display' => 'Analytics',
        'field' => 'umami_analytics',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracker attributes
    |--------------------------------------------------------------------------
    |
    | Behaviour flags rendered as data-* attributes on the {{ umami }} script
    | tag. See https://docs.umami.is/docs/tracker-configuration.
    |
    */

    'tracking' => [
        // data-domains: restricts the tracker to these hostnames, so hits from
        // other domains/IPs pointing at this app aren't recorded. Leave null to
        // derive "host,www.host" from `app.url`, set a comma-separated list to
        // override, or `false` to disable the restriction entirely.
        'domains' => env('UMAMI_TRACKING_DOMAINS'),

        // data-performance: collects Core Web Vitals (LCP, CLS, INP, TTFB).
        'performance' => (bool) env('UMAMI_TRACKING_PERFORMANCE', true),

        // data-do-not-track: honours the visitor's browser "Do Not Track" setting.
        'do_not_track' => (bool) env('UMAMI_TRACKING_DO_NOT_TRACK', true),
    ],
];
