<?php

namespace App\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * API Response Helper Trait
 *
 * Provides standardized methods for creating consistent API responses.
 * All API controllers should use this trait to ensure response consistency.
 *
 * Standard Response Format:
 * {
 *   "success": true,
 *   "message": "Operation message",
 *   "data": { ... },
 *   "meta": { ... },
 *   "errors": { ... }
 * }
 */
trait ApiResponse
{
    /**
     * Default success status code.
     *
     * @var int
     */
    public const HTTP_OK = 200;

    /**
     * Resource created status code.
     *
     * @var int
     */
    public const HTTP_CREATED = 201;

    /**
     * No content status code.
     *
     * @var int
     */
    public const HTTP_NO_CONTENT = 204;

    /**
     * Client error status codes.
     *
     * @var int
     */
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;

    /**
     * Server error status code.
     *
     * @var int
     */
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * Rate limit exceeded status code.
     *
     * @var int
     */
    public const HTTP_TOO_MANY_REQUESTS = 429;

    /**
     * Create a successful response with data.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @param array $meta
     * @return JsonResponse
     */
    public function success(mixed $data = null, ?string $message = null, int $statusCode = self::HTTP_OK, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message ?? config('api_response.messages.success', 'Operation completed successfully'),
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Create a created response (201).
     *
     * @param mixed $data
     * @param string|null $message
     * @return JsonResponse
     */
    public function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return $this->success(
            $data,
            $message ?? config('api_response.messages.created', 'Resource created successfully'),
            self::HTTP_CREATED
        );
    }

    /**
     * Create a success response with a list of resources.
     *
     * @param \Illuminate\Http\Resources\Json\JsonResource|\Illuminate\Http\Resources\Json\ResourceCollection|array $resource
     * @param \Illuminate\Pagination\LengthAwarePaginator|null $paginator
     * @param string|null $message
     * @return JsonResponse
     */
    public function list(JsonResource|ResourceCollection|array $resource, ?LengthAwarePaginator $paginator = null, ?string $message = null): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message ?? config('api_response.messages.listed', 'Resources listed successfully'),
        ];

        if ($resource instanceof ResourceCollection) {
            $response['data'] = $resource->response()->getData(true)['data'];
        } elseif ($resource instanceof JsonResource) {
            $response['data'] = $resource->toArray(request());
        } else {
            $response['data'] = $resource;
        }

        if ($paginator) {
            $response['meta'] = [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ];

            $response['links'] = [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ];
        }

        return response()->json($response);
    }

    /**
     * Create a success response with pagination.
     *
     * @param mixed $data
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @param string|null $message
     * @return JsonResponse
     */
    public function paginated(mixed $data, LengthAwarePaginator $paginator, ?string $message = null): JsonResponse
    {
        return $this->list($data, $paginator, $message);
    }

    /**
     * Create an error response.
     *
     * @param string $message
     * @param string $errorCode
     * @param int $statusCode
     * @param array|null $errors
     * @param array|null $debug
     * @return JsonResponse
     */
    public function error(
        string $message,
        string $errorCode = 'SERVER_ERROR',
        int $statusCode = self::HTTP_INTERNAL_SERVER_ERROR,
        ?array $errors = null,
        ?array $debug = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        // Only include debug info in development
        if (config('app.debug') && $debug !== null) {
            $response['debug'] = $debug;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Create a validation error response (422).
     *
     * @param array $errors
     * @param string|null $message
     * @return JsonResponse
     */
    public function validationError(array $errors, ?string $message = null): JsonResponse
    {
        return $this->error(
            $message ?? 'The given data was invalid.',
            config('api_response.error_codes.VALIDATION_ERROR', 'VALIDATION_ERROR'),
            self::HTTP_UNPROCESSABLE_ENTITY,
            $errors
        );
    }

    /**
     * Create an authentication error response (401).
     *
     * @param string|null $message
     * @return JsonResponse
     */
    public function unauthorized(?string $message = null): JsonResponse
    {
        return $this->error(
            $message ?? 'Unauthenticated.',
            config('api_response.error_codes.AUTHENTICATION_ERROR', 'AUTHENTICATION_ERROR'),
            self::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Create a forbidden error response (403).
     *
     * @param string|null $message
     * @return JsonResponse
     */
    public function forbidden(?string $message = null): JsonResponse
    {
        return $this->error(
            $message ?? 'Unauthorized to perform this action.',
            config('api_response.error_codes.AUTHORIZATION_ERROR', 'AUTHORIZATION_ERROR'),
            self::HTTP_FORBIDDEN
        );
    }

    /**
     * Create a not found error response (404).
     *
     * @param string|null $message
     * @param string|null $resource
     * @return JsonResponse
     */
    public function notFound(?string $message = null, ?string $resource = null): JsonResponse
    {
        $message = $message ?? ($resource ? "{$resource} not found." : 'Resource not found.');

        return $this->error(
            $message,
            config('api_response.error_codes.NOT_FOUND', 'NOT_FOUND'),
            self::HTTP_NOT_FOUND
        );
    }

    /**
     * Create a bad request error response (400).
     *
     * @param string $message
     * @param string|null $errorCode
     * @return JsonResponse
     */
    public function badRequest(string $message, ?string $errorCode = null): JsonResponse
    {
        return $this->error(
            $message,
            $errorCode ?? config('api_response.error_codes.BAD_REQUEST', 'BAD_REQUEST'),
            self::HTTP_BAD_REQUEST
        );
    }

    /**
     * Create a rate limit exceeded response (429).
     *
     * @param string|null $message
     * @param int|null $retryAfter
     * @return JsonResponse
     */
    public function rateLimitExceeded(?string $message = null, ?int $retryAfter = null): JsonResponse
    {
        $response = $this->error(
            $message ?? 'Too many requests. Please try again later.',
            config('api_response.error_codes.RATE_LIMIT_EXCEEDED', 'RATE_LIMIT_EXCEEDED'),
            self::HTTP_TOO_MANY_REQUESTS
        );

        if ($retryAfter) {
            $response->headers->set('Retry-After', $retryAfter);
        }

        return $response;
    }

    /**
     * Create a server error response (500).
     *
     * @param string|null $message
     * @param \Throwable|null $exception
     * @return JsonResponse
     */
    public function serverError(?string $message = null, ?\Throwable $exception = null): JsonResponse
    {
        $debug = null;

        if (config('app.debug') && $exception) {
            $debug = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return $this->error(
            $message ?? 'Something went wrong on our server.',
            config('api_response.error_codes.SERVER_ERROR', 'SERVER_ERROR'),
            self::HTTP_INTERNAL_SERVER_ERROR,
            null,
            $debug
        );
    }

    /**
     * Create a no content response (204).
     *
     * @return JsonResponse
     */
    public function noContent(): JsonResponse
    {
        return response()->json(null, self::HTTP_NO_CONTENT);
    }

    /**
     * Create a accepted response (202).
     *
     * @param mixed $data
     * @param string|null $message
     * @return JsonResponse
     */
    public function accepted(mixed $data = null, ?string $message = null): JsonResponse
    {
        return $this->success($data, $message ?? 'Request accepted.', self::HTTP_NO_CONTENT);
    }

    /**
     * Create a conflict response (409).
     *
     * @param string $message
     * @return JsonResponse
     */
    public function conflict(string $message): JsonResponse
    {
        return $this->error(
            $message,
            config('api_response.error_codes.CONFLICT', 'CONFLICT'),
            409
        );
    }

    /**
     * Transform a resource collection for consistent response.
     *
     * @param \Illuminate\Http\Resources\Json\ResourceCollection $resource
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function resource(ResourceCollection $resource, ?string $message = null): JsonResponse
    {
        return $resource->additional([
            'success' => true,
            'message' => $message ?? config('api_response.messages.retrieved', 'Resource retrieved successfully'),
        ])->response();
    }

    /**
     * Transform a single resource for consistent response.
     *
     * @param \Illuminate\Http\Resources\Json\JsonResource $resource
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function resourceItem(JsonResource $resource, ?string $message = null): JsonResponse
    {
        return $resource->additional([
            'success' => true,
            'message' => $message ?? config('api_response.messages.retrieved', 'Resource retrieved successfully'),
        ])->response();
    }
}

