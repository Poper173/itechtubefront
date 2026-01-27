<?php

/**
 * CORS (Cross-Origin Resource Sharing) Configuration
 *
 * This file configures CORS settings for the API.
 * CORS allows cross-origin requests from specified origins.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Configuration
    |--------------------------------------------------------------------------
    |
    | This file configures Cross-Origin Resource Sharing (CORS) for your API.
    |
    | CORS is a mechanism that allows restricted resources on a web page to be
    | requested from another domain outside the domain from which the first
    | resource was served.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Configure which origins are allowed to make cross-origin requests.
    | Use ['*'] to allow all origins, or add specific domains.
    |
    | Examples:
    |   'allowed_origins' => ['http://localhost:3000'],
    |   'allowed_origins' => ['https://myapp.com', 'https://www.myapp.com'],
    |
    | For development, you might want to allow multiple ports:
    |   'allowed_origins' => ['http://localhost:3000', 'http://localhost:5173'],
    |
    */
    'allowed_origins' => [
        env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000'),
        env('CORS_ALLOWED_ORIGINS_2', 'http://localhost:5173'),
        env('CORS_ALLOWED_ORIGINS_3', 'http://127.0.0.1:3000'),
        env('CORS_ALLOWED_ORIGINS_4', 'http://127.0.0.1:5173'),
        env('CORS_ALLOWED_ORIGINS_5', 'http://127.0.0.1:8000'),
        // Allow all origins for development (file:// protocol)
        '*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | Configure patterns to allow dynamic origins based on regex or patterns.
    | Useful when you want to allow all subdomains of a domain.
    |
    | Examples:
    |   'allowed_origins_patterns' => [
    |       '/^https?:\/\/.*\.myapp\.com$/',
    |   ],
    |
    */
    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Allowed HTTP Methods
    |--------------------------------------------------------------------------
    |
    | Configure which HTTP methods are allowed for cross-origin requests.
    | These are the methods that will be allowed in the Access-Control-Allow-Methods header.
    |
    */
    'allowed_methods' => [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed HTTP Headers
    |--------------------------------------------------------------------------
    |
    | Configure which HTTP headers are allowed in cross-origin requests.
    | These are the headers that will be allowed in the Access-Control-Allow-Headers header.
    |
    | Note: Authorization and Content-Type are included by default.
    |
    */
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
        'Access-Control-Request-Method',
        'Access-Control-Request-Headers',
        'X-CSRF-TOKEN',
        'X-API-Key',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | Configure which response headers should be exposed to the browser.
    | These headers will be available in the Access-Control-Expose-Headers header.
    |
    | Examples:
    |   'exposed_headers' => ['X-Custom-Header', 'X-RateLimit-Limit'],
    |
    */
    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
        'X-Total-Count',
        'X-Current-Page',
        'X-Last-Page',
    ],

    /*
    |--------------------------------------------------------------------------
    | Max Age (Seconds)
    |--------------------------------------------------------------------------
    |
    | Configure how long the results of a preflight request can be cached.
    | This sets the Access-Control-Max-Age header in seconds.
    |
    | Set to false to disable caching.
    |
    */
    'max_age' => 86400, // 24 hours

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Configure whether the response to the request can be exposed when
    | the credentials flag is true. When used as part of a response to a
    | preflight request, this indicates whether the actual request can
    | be made using credentials.
    |
    | Note: This cannot be set to true if 'allowed_origins' includes ['*']
    |
    */
    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', false),

    /*
    |--------------------------------------------------------------------------
    | CORS Paths
    |--------------------------------------------------------------------------
    |
    | Configure which paths should have CORS headers applied.
    | By default, all routes in the 'api' middleware group will have CORS.
    |
    | Examples:
    |   'paths' => ['api/*', 'api/v1/*'],
    |
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Request Types
    |--------------------------------------------------------------------------
    |
    | Determines if preflight requests should be handled.
    | Set to false to disable CORS for OPTIONS requests.
    |
    */
    'allowed_requests' => true,

    /*
    |--------------------------------------------------------------------------
    | Force Preflight Cache
    |--------------------------------------------------------------------------
    |
    | Configure whether to force the Preflight Cache to be cleared.
    |
    */
    'force_preflight_cache' => false,
];

