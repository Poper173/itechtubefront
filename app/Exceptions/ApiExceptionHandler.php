<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * API Exception Handler
 *
 * Provides consistent JSON error responses for API requests.
 */
class ApiExceptionHandler
{
    /**
     * Handle an exception and return a JSON response.
     */
    public function handle(Throwable $e, Request $request): JsonResponse
    {
        $statusCode = $this->getStatusCode($e);
        $errorCode  = $this->getErrorCode($e);
        $message    = $this->getMessage($e);

        return $this->buildResponse($e, $request, $statusCode, $errorCode, $message);
    }

    /**
     * Determine HTTP status code.
     */
    protected function getStatusCode(Throwable $e): int
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        if ($e instanceof ValidationException) {
            return 422;
        }

        if ($e instanceof AuthenticationException) {
            return 401;
        }

        if ($e instanceof AuthorizationException) {
            return 403;
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return 404;
        }

        return 500;
    }

    /**
     * Determine application error code.
     */
    protected function getErrorCode(Throwable $e): string
    {
        return match (true) {
            $e instanceof AuthenticationException => 'UNAUTHORIZED',
            $e instanceof AuthorizationException => 'FORBIDDEN',
            $e instanceof ValidationException     => 'VALIDATION_ERROR',
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException   => 'NOT_FOUND',
            default                               => 'SERVER_ERROR',
        };
    }

    /**
     * Determine error message.
     */
    protected function getMessage(Throwable $e): string
    {
        if (config('app.debug')) {
            return $e->getMessage() ?: 'Server error';
        }

        return match (true) {
            $e instanceof AuthenticationException => 'Authentication required.',
            $e instanceof AuthorizationException => 'You are not authorized to perform this action.',
            $e instanceof ValidationException     => 'The provided data is invalid.',
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException   => 'Resource not found.',
            default                               => 'Something went wrong.',
        };
    }

    /**
     * Build final JSON response.
     */
    protected function buildResponse(
        Throwable $e,
        Request $request,
        int $statusCode,
        string $errorCode,
        string $message
    ): JsonResponse {
        $response = [
            'success'    => false,
            'message'    => $message,
            'error_code' => $errorCode,
        ];

        if ($e instanceof ValidationException) {
            $response['errors'] = $e->errors();
        }

        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ];
        }

        if ($request->hasHeader('X-Request-ID')) {
            $response['request_id'] = $request->header('X-Request-ID');
        }

        return response()->json($response, $statusCode);
    }
}
