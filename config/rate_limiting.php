<?php

/**
 * Rate Limiting Configuration
 *
 * This file configures rate limits for API endpoints.
 * Rate limiting protects your API from abuse and ensures fair usage.
 *
 * @see https://laravel.com/docs/routing#rate-limiting
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Global API Rate Limit
    |--------------------------------------------------------------------------
    |
    | Default rate limit applied to all authenticated API requests.
    | This is configured in RouteServiceProvider but can be customized here.
    |
    */
    'global' => [
        'enabled' => true,
        'max_attempts' => 60,
        'decay_seconds' => 60, // 1 minute
        'prefix' => 'global',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Rate Limits
    |--------------------------------------------------------------------------
    |
    | Rate limits for authentication endpoints (login, register).
    | These endpoints should be more strictly limited to prevent brute force attacks.
    |
    */
    'auth' => [
        'login' => [
            'max_attempts' => 5,
            'decay_seconds' => 300, // 5 minutes
            'prefix' => 'auth-login',
            'message' => 'Too many login attempts. Please try again in :seconds seconds.',
        ],
        'register' => [
            'max_attempts' => 10,
            'decay_seconds' => 60, // 1 minute
            'prefix' => 'auth-register',
            'message' => 'Too many registration attempts. Please try again in :seconds seconds.',
        ],
        'password_reset' => [
            'max_attempts' => 3,
            'decay_seconds' => 3600, // 1 hour
            'prefix' => 'auth-password-reset',
            'message' => 'Too many password reset attempts. Please try again in :seconds seconds.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Endpoint Rate Limits
    |--------------------------------------------------------------------------
    |
    | Specific rate limits for different API endpoint categories.
    |
    */
    'endpoints' => [
        // Read operations (less strict)
        'read' => [
            'max_attempts' => 120,
            'decay_seconds' => 60, // 1 minute
            'prefix' => 'api-read',
            'endpoints' => [
                'GET /api/videos',
                'GET /api/videos/*',
                'GET /api/categories',
                'GET /api/categories/*',
                'GET /api/playlists/*',
                'GET /api/history',
                'GET /api/videos/search',
            ],
        ],

        // Write operations (more strict)
        'write' => [
            'max_attempts' => 30,
            'decay_seconds' => 60, // 1 minute
            'prefix' => 'api-write',
            'endpoints' => [
                'POST /api/videos',
                'PUT /api/videos/*',
                'DELETE /api/videos/*',
                'POST /api/categories',
                'PUT /api/categories/*',
                'DELETE /api/categories/*',
            ],
        ],

        // High-cost operations (most strict)
        'high_cost' => [
            'max_attempts' => 10,
            'decay_seconds' => 60, // 1 minute
            'prefix' => 'api-high-cost',
            'message' => 'This operation is rate limited. Please try again later.',
            'endpoints' => [
                'POST /api/playlists/*/videos',
                'PUT /api/playlists/*/reorder',
                'DELETE /api/playlists/*/videos/*',
            ],
        ],

        // Streaming operations
        'streaming' => [
            'max_attempts' => 200,
            'decay_seconds' => 60, // 1 minute
            'prefix' => 'api-streaming',
            'endpoints' => [
                'GET /api/videos/*/stream',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User-Based Rate Limits
    |--------------------------------------------------------------------------
    |
    | Rate limits that vary based on user role or subscription level.
    |
    */
    'user_tiers' => [
        'free' => [
            'multiplier' => 1.0,
            'description' => 'Default rate limits for free users',
        ],
        'premium' => [
            'multiplier' => 2.0,
            'description' => '2x rate limits for premium users',
        ],
        'admin' => [
            'multiplier' => 5.0,
            'description' => '5x rate limits for administrators',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Response Settings
    |--------------------------------------------------------------------------
    |
    | Configure how rate limit exceeded responses are returned.
    |
    */
    'response' => [
        'format' => 'json', // json, header
        'include_retry_after' => true,
        'include_limit_remaining' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | IP-Based Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Settings for IP-based rate limiting (for unauthenticated requests).
    |
    */
    'ip_based' => [
        'enabled' => true,
        'max_attempts' => 30,
        'decay_seconds' => 60,
        'prefix' => 'ip',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Cache Driver
    |--------------------------------------------------------------------------
    |
    | Configure which cache driver to use for storing rate limit counters.
    | Using 'array' is not recommended for production as it doesn't persist.
    |
    */
    'cache_driver' => env('RATE_LIMIT_CACHE_DRIVER', 'file'), // file, redis, memcached, array
];

