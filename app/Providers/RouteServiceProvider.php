<?php

namespace App\Providers;

use App\Http\Middleware\InputSanitization;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Configure rate limiting
        $this->configureRateLimiting();

        $this->routes(function () {
            // Register middleware aliases
            Route::aliasMiddleware('sanitize.input', InputSanitization::class);

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * This method sets up multiple rate limiters for different API endpoints:
     * - api: General API requests (60 per minute by default)
     * - login: Login attempts (5 per 5 minutes)
     * - register: Registration attempts (10 per minute)
     * - upload: File upload operations (10 per minute)
     * - streaming: Video streaming requests (200 per minute)
     *
     * @see config/rate_limiting.php for detailed configuration
     */
    protected function configureRateLimiting(): void
    {
        // General API rate limiter (default)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many requests. Please try again in one minute.',
                        'error_code' => 'RATE_LIMIT_EXCEEDED',
                        'retry_after' => 60,
                    ], 429, $headers);
                });
        });

        // Authentication rate limiters
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many login attempts. Please try again in 5 minutes.',
                        'error_code' => 'RATE_LIMIT_EXCEEDED',
                        'retry_after' => 300,
                    ], 429, $headers);
                });
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many registration attempts. Please try again in one minute.',
                        'error_code' => 'RATE_LIMIT_EXCEEDED',
                        'retry_after' => 60,
                    ], 429, $headers);
                });
        });

        // File upload rate limiter
        RateLimiter::for('upload', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many file uploads. Please try again in one minute.',
                        'error_code' => 'RATE_LIMIT_EXCEEDED',
                        'retry_after' => 60,
                    ], 429, $headers);
                });
        });

        // Video streaming rate limiter (more permissive)
        RateLimiter::for('streaming', function (Request $request) {
            return Limit::perMinute(200)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Search rate limiter
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many search requests. Please try again in one minute.',
                        'error_code' => 'RATE_LIMIT_EXCEEDED',
                        'retry_after' => 60,
                    ], 429, $headers);
                });
        });
    }
}
