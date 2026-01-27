<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PlaylistController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\WatchHistoryController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
| Security Features Applied:
| - CORS: Configured in config/cors.php
| - Rate Limiting: Configured in RouteServiceProvider and config/rate_limiting.php
| - Input Sanitization: Applied via 'sanitize.input' middleware
| - Authentication: Sanctum for token-based auth
|
*/

// User info (sanctum auth)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// User's liked videos
Route::get('/user/liked-videos', [VideoController::class, 'likedVideos'])
    ->middleware('auth:sanctum');

// Authentication routes (public, with rate limiting)
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:register');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

Route::get('/me', [AuthController::class, 'me'])
    ->middleware('auth:sanctum');

// User profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/password', [UserController::class, 'changePassword']);
});

// Categories (public read, auth required for modifications)
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('categories', CategoryController::class)
    ->only(['store', 'update', 'destroy'])
    ->middleware('auth:sanctum');

// Videos (public read, auth required for modifications and upload)
Route::get('/videos', [VideoController::class, 'index'])
    ->middleware('sanitize.input');

// Get videos by category (supports both ID and slug)
Route::get('/videos/category/{category}', [VideoController::class, 'videosByCategory'])
    ->middleware(['sanitize.input', 'throttle:60,1']);

Route::get('/videos/all', [VideoController::class, 'allVideos'])
    ->middleware('sanitize.input');

Route::get('/videos/{video}', [VideoController::class, 'show'])
    ->middleware('sanitize.input');

Route::get('/videos/{video}/stream', [VideoController::class, 'stream'])
    ->middleware('throttle:streaming')
    ->name('api.videos.stream');

Route::post('/videos/{video}/watch', [VideoController::class, 'recordWatch'])
    ->middleware('auth:sanctum')
    ->name('api.videos.watch');

// Video likes
Route::post('/videos/{video}/like', [VideoController::class, 'toggleLike'])
    ->middleware('auth:sanctum');
Route::delete('/videos/{video}/like', [VideoController::class, 'toggleLike'])
    ->middleware('auth:sanctum');
Route::get('/videos/{video}/like/status', [VideoController::class, 'getLikeStatus']);
Route::get('/videos/{video}/likes', [VideoController::class, 'getLikes']);

// Video search (with rate limiting)
Route::get('/videos/search', [VideoController::class, 'search'])
    ->middleware(['sanitize.input', 'throttle:search']);

// Quick search by name (for autocomplete/typeahead)
Route::get('/videos/search/name', [VideoController::class, 'searchByName'])
    ->middleware(['sanitize.input', 'throttle:search']);

// Authenticated video operations (with upload rate limiting)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/videos', [VideoController::class, 'store'])
        ->middleware('throttle:upload');

    // Video CRUD endpoints
    Route::get('/videos/{video}/edit', [VideoController::class, 'edit'])
        ->middleware('sanitize.input');
    Route::put('/videos/{video}', [VideoController::class, 'update'])
        ->middleware('sanitize.input');
    Route::patch('/videos/{video}', [VideoController::class, 'update'])
        ->middleware('sanitize.input');
    Route::delete('/videos/{video}', [VideoController::class, 'destroy']);

    Route::get('/my-videos', [VideoController::class, 'myVideos'])
        ->middleware('sanitize.input');



    // Chunked upload endpoints
    Route::post('/videos/upload/init', [VideoController::class, 'initChunkedUpload'])
        ->middleware(['throttle:upload', 'sanitize.input'])
        ->name('api.videos.upload.init');

    Route::post('/videos/upload/chunk', [VideoController::class, 'uploadChunk'])
        ->middleware(['throttle:upload', 'sanitize.input'])
        ->name('api.videos.upload.chunk');

    Route::post('/videos/upload/complete', [VideoController::class, 'completeChunkedUpload'])
        ->middleware(['throttle:upload', 'sanitize.input'])
        ->name('api.videos.upload.complete');

    Route::get('/videos/upload/status/{sessionId}', [VideoController::class, 'uploadStatus'])
        ->middleware('sanitize.input')
        ->name('api.videos.upload.status');

    Route::delete('/videos/upload/abort/{sessionId}', [VideoController::class, 'abortUpload'])
        ->name('api.videos.upload.abort');

    // Server-side video operations
    Route::post('/videos/upload-from-server', [VideoController::class, 'uploadFromServer'])
        ->middleware(['auth:sanctum', 'sanitize.input']);

    Route::get('/videos/{video}/stream-info', [VideoController::class, 'getStreamInfo'])
        ->middleware('sanitize.input');

    Route::get('/videos/server/list', [VideoController::class, 'listServerVideos'])
        ->middleware(['auth:sanctum', 'sanitize.input']);

    Route::post('/videos/import-from-server', [VideoController::class, 'importFromServer'])
        ->middleware(['auth:sanctum', 'sanitize.input']);

    // Video download (requires authentication)
    Route::get('/videos/{video}/download', [VideoController::class, 'download'])
        ->middleware(['auth:sanctum'])
        ->name('api.videos.download');

    Route::get('/videos/{video}/download/info', [VideoController::class, 'getDownloadInfo'])
        ->middleware(['auth:sanctum', 'sanitize.input']);
});

// Playlists (all operations require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/playlists', [PlaylistController::class, 'index'])
        ->middleware('sanitize.input');
    Route::post('/playlists', [PlaylistController::class, 'store'])
        ->middleware('sanitize.input');
    Route::get('/playlists/{playlist}', [PlaylistController::class, 'show'])
        ->middleware('sanitize.input');
    Route::put('/playlists/{playlist}', [PlaylistController::class, 'update'])
        ->middleware('sanitize.input');
    Route::delete('/playlists/{playlist}', [PlaylistController::class, 'destroy']);

    // Playlist video management
    Route::post('/playlists/{playlist}/videos', [PlaylistController::class, 'addVideo'])
        ->middleware('sanitize.input');
    Route::delete('/playlists/{playlist}/videos/{video}', [PlaylistController::class, 'removeVideo']);
    Route::put('/playlists/{playlist}/reorder', [PlaylistController::class, 'reorderVideos'])
        ->middleware('sanitize.input');
});

// Watch history (all operations require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/history', [WatchHistoryController::class, 'index'])
        ->middleware('sanitize.input');
    Route::post('/history', [WatchHistoryController::class, 'store'])
        ->middleware('sanitize.input');
    Route::get('/history/video/{videoId}', [WatchHistoryController::class, 'show'])
        ->middleware('sanitize.input');
    Route::put('/history/video/{videoId}', [WatchHistoryController::class, 'update'])
        ->middleware('sanitize.input');
    Route::delete('/history/{id}', [WatchHistoryController::class, 'destroy']);
    Route::delete('/history', [WatchHistoryController::class, 'clearAll']);
    Route::get('/history/continue-watching', [WatchHistoryController::class, 'continueWatching'])
        ->middleware('sanitize.input');
});

// Subscriptions (all operations require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Toggle subscription for a channel
    Route::post('/channels/{channelId}/subscribe', [SubscriptionController::class, 'toggleSubscription']);

    // Get subscription status for a channel
    Route::get('/channels/{channelId}/subscription', [SubscriptionController::class, 'getSubscriptionStatus']);

    // Get user's subscriptions (channels they follow)
    Route::get('/subscriptions', [SubscriptionController::class, 'mySubscriptions']);

    // Get subscribers of a channel
    Route::get('/channels/{channelId}/subscribers', [SubscriptionController::class, 'getChannelSubscribers']);

    // Check subscription status for multiple channels at once
    Route::post('/subscriptions/check', [SubscriptionController::class, 'checkMultipleSubscriptions']);

    // Get user's subscription statistics
    Route::get('/subscriptions/stats', [SubscriptionController::class, 'getMySubscriptionStats']);
});

// Admin Routes (auth:sanctum + admin role check)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Dashboard stats
    Route::get('/admin/stats', [AdminController::class, 'getStats']);
    Route::get('/admin/overview', [AdminController::class, 'overview']);

    // User management
    Route::get('/admin/users', [AdminController::class, 'getUsers']);
    Route::post('/admin/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);
    Route::post('/admin/users/{id}/role', [AdminController::class, 'updateUserRole']);

    // Video management
    Route::get('/admin/videos', [AdminController::class, 'getVideos']);
    Route::get('/admin/videos/pending', [AdminController::class, 'getPendingVideos']);
    Route::post('/admin/videos/{id}/approve', [AdminController::class, 'approveVideo']);
    Route::post('/admin/videos/{id}/reject', [AdminController::class, 'rejectVideo']);
    Route::delete('/admin/videos/{id}', [AdminController::class, 'deleteVideo']);

    // Recent users for dashboard
    Route::get('/admin/users/recent', [AdminController::class, 'getRecentUsers']);

    // Category management (admin can create/update/delete)
    Route::post('/admin/categories', [AdminController::class, 'storeCategory']);
    Route::put('/admin/categories/{id}', [AdminController::class, 'updateCategory']);
    Route::delete('/admin/categories/{id}', [AdminController::class, 'deleteCategory']);
});

// Creator/Channel Routes (auth:sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Channel profile
    Route::get('/creator/channel', [App\Http\Controllers\Api\CreatorController::class, 'getChannelProfile']);
    Route::put('/creator/channel', [App\Http\Controllers\Api\CreatorController::class, 'updateChannelProfile']);

    // Stream management
    Route::get('/creator/stream/key', [App\Http\Controllers\Api\CreatorController::class, 'getStreamKey']);
    Route::post('/creator/stream/regenerate-key', [App\Http\Controllers\Api\CreatorController::class, 'regenerateStreamKey']);
    Route::post('/creator/stream/start', [App\Http\Controllers\Api\CreatorController::class, 'startStream']);
    Route::post('/creator/stream/stop', [App\Http\Controllers\Api\CreatorController::class, 'stopStream']);
    Route::get('/creator/stream/status', [App\Http\Controllers\Api\CreatorController::class, 'getStreamStatus']);
    Route::put('/creator/stream/viewers', [App\Http\Controllers\Api\CreatorController::class, 'updateStreamViewers']);

    // Channel analytics
    Route::get('/creator/stats', [App\Http\Controllers\Api\CreatorController::class, 'getChannelStats']);
    Route::get('/creator/analytics', [App\Http\Controllers\Api\CreatorController::class, 'getChannelAnalytics']);
});

// Public channel profile (viewers can see channel info)
Route::get('/channels/{channelId}', [App\Http\Controllers\Api\CreatorController::class, 'getPublicChannel']);

// Live streams (public - for viewers to discover and watch live streams)
Route::get('/live', [App\Http\Controllers\Api\CreatorController::class, 'getAllLiveStreams']);
Route::get('/live/{channelId}', [App\Http\Controllers\Api\CreatorController::class, 'getLiveStream']);

// Live stream chat and viewer tracking (public for viewing, auth for sending)
Route::get('/live/{channelId}/chat', [App\Http\Controllers\Api\CreatorController::class, 'getChatMessages']);
Route::post('/live/{channelId}/chat', [App\Http\Controllers\Api\CreatorController::class, 'sendChatMessage'])
    ->middleware('auth:sanctum');
Route::post('/live/{channelId}/join', [App\Http\Controllers\Api\CreatorController::class, 'joinStream']);
Route::post('/live/{channelId}/leave', [App\Http\Controllers\Api\CreatorController::class, 'leaveStream']);
Route::get('/live/{channelId}/viewers', [App\Http\Controllers\Api\CreatorController::class, 'getViewerCount']);

// Creator stream monitoring (auth required)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/creator/stream/monitor', [App\Http\Controllers\Api\CreatorController::class, 'getStreamMonitor']);
});

