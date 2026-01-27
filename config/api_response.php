<?php

/**
 * API Response Formatter
 *
 * Provides standardized response formatting for all API endpoints.
 * Ensures consistent JSON structure across all responses.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | API Response Settings
    |--------------------------------------------------------------------------
    |
    | This file configures how API responses are formatted across the
    | application. This ensures consistency in all JSON responses.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Response Structure
    |--------------------------------------------------------------------------
    |
    | Default structure for API responses:
    | {
    |   "success": true,
    |   "message": "Operation completed successfully",
    |   "data": { ... },
    |   "meta": { ... }
    | }
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Success Messages
    |--------------------------------------------------------------------------
    |
    | Default messages for common operations.
    |
    */
    'messages' => [
        'created' => 'Resource created successfully',
        'updated' => 'Resource updated successfully',
        'deleted' => 'Resource deleted successfully',
        'retrieved' => 'Resource retrieved successfully',
        'listed' => 'Resources listed successfully',
        'success' => 'Operation completed successfully',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Default pagination limits and settings.
    |
    */
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Codes
    |--------------------------------------------------------------------------
    |
    | Standardized error codes for API responses.
    | These codes help clients identify specific error types.
    |
    */
    'error_codes' => [
        'VALIDATION_ERROR' => 'VALIDATION_ERROR',
        'AUTHENTICATION_ERROR' => 'AUTHENTICATION_ERROR',
        'AUTHORIZATION_ERROR' => 'AUTHORIZATION_ERROR',
        'NOT_FOUND' => 'NOT_FOUND',
        'RATE_LIMIT_EXCEEDED' => 'RATE_LIMIT_EXCEEDED',
        'SERVER_ERROR' => 'SERVER_ERROR',
        'BAD_REQUEST' => 'BAD_REQUEST',
        'CONFLICT' => 'CONFLICT',
    ],
];

