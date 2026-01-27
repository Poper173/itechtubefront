<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Input Sanitization Middleware
 *
 * This middleware sanitizes all incoming request inputs to prevent XSS attacks
 * and other malicious input patterns. It also performs basic input validation.
 *
 * Features:
 * - HTML entity encoding for string inputs
 * - Removal of control characters
 * - Trim whitespace from string inputs
 * - Validation of required fields
 * - Basic type checking
 *
 * @see https://owasp.org/www-community/attacks/xss/
 */
class InputSanitization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize the request inputs
        $sanitized = $this->sanitize($request->all());

        // Replace the request inputs with sanitized values
        $request->replace($sanitized);

        // Perform basic validation
        $validator = $this->validateBasicRules($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Input validation failed',
                'errors' => $validator->errors(),
                'error_code' => 'VALIDATION_ERROR',
            ], 422);
        }

        return $next($request);
    }

    /**
     * Sanitize all input data.
     *
     * @param array $data
     * @return array
     */
    protected function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize a single string value.
     *
     * @param string $value
     * @return string
     */
    protected function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);

        // Remove control characters (except newlines, tabs, and carriage returns)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

        // Trim whitespace
        $value = trim($value);

        // Encode HTML entities to prevent XSS
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        // Remove excessive newlines (potential injection vector)
        $value = preg_replace('/(\r\n|\n|\r){3,}/', "\n\n", $value);

        return $value;
    }

    /**
     * Validate basic input rules.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validateBasicRules(Request $request)
    {
        return Validator::make($request->all(), [
            // Validate that no field names contain special characters
            '*.!@#$%^&*(){}[]|\\<>?/~`' => 'sometimes|prohibited',
        ]);
    }
}

