<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WatchHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * WatchHistoryController
 *
 * Handles watch history tracking and retrieval.
 * All operations require authentication.
 * Optimized with eager loading to prevent N+1 queries.
 */
class WatchHistoryController extends Controller
{
    /**
     * Get the authenticated user's watch history.
     * Optimized with eager loading to prevent N+1 queries.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->watchHistory()
            ->with(['video' => function ($query) {
                // Optimize: only load needed columns from related models
                $query->select(['id', 'title', 'thumbnail_path', 'duration', 'user_id', 'category_id'])
                    ->with(['user:id,name,avatar', 'category:id,name,slug']);
            }]);

        // Filter for incomplete watches (continue watching)
        if ($request->has('incomplete') && $request->boolean('incomplete')) {
            $query->incomplete();
        }

        // Filter for completed watches
        if ($request->has('completed') && $request->boolean('completed')) {
            $query->completed();
        }

        // Sort by most recent
        $history = $query->mostRecent()->paginate(20);

        return response()->json([
            'message' => 'Watch history retrieved successfully',
            'data' => $history,
        ]);
    }

    /**
     * Record or update watch progress for a video.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'video_id' => ['required', 'exists:videos,id'],
                'progress' => ['nullable', 'integer', 'min:0'],
                'completed' => ['nullable', 'boolean'],
            ]);

            $user = $request->user();
            $videoId = $validated['video_id'];
            $progress = $validated['progress'] ?? 0;
            $completed = $validated['completed'] ?? false;

            // Find or create watch history entry
            $history = WatchHistory::updateOrCreate(
                ['user_id' => $user->id, 'video_id' => $videoId],
                [
                    'progress' => $progress,
                    'completed' => $completed,
                ]
            );

            return response()->json([
                'message' => 'Watch progress recorded successfully',
                'data' => $history->load(['video' => function ($query) {
                    $query->with(['user', 'category']);
                }]),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to record watch progress',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update watch progress for a specific video.
     *
     * @param Request $request
     * @param int $videoId
     * @return JsonResponse
     */
    public function update(Request $request, int $videoId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'progress' => ['nullable', 'integer', 'min:0'],
                'completed' => ['nullable', 'boolean'],
            ]);

            $user = $request->user();

            // Find watch history entry
            $history = WatchHistory::where('user_id', $user->id)
                ->where('video_id', $videoId)
                ->first();

            if (!$history) {
                return response()->json([
                    'message' => 'Watch history entry not found',
                ], 404);
            }

            // Update progress
            if (isset($validated['progress'])) {
                $history->updateProgress($validated['progress']);
            }

            // Update completed status
            if (isset($validated['completed'])) {
                $history->completed = $validated['completed'];
                $history->save();
            }

            return response()->json([
                'message' => 'Watch progress updated successfully',
                'data' => $history->fresh()->load(['video' => function ($query) {
                    $query->with(['user', 'category']);
                }]),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update watch progress',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get watch progress for a specific video.
     *
     * @param Request $request
     * @param int $videoId
     * @return JsonResponse
     */
    public function show(Request $request, int $videoId): JsonResponse
    {
        $user = $request->user();

        $history = WatchHistory::where('user_id', $user->id)
            ->where('video_id', $videoId)
            ->with(['video' => function ($query) {
                $query->with(['user', 'category']);
            }])
            ->first();

        if (!$history) {
            return response()->json([
                'message' => 'No watch history found for this video',
                'data' => null,
            ]);
        }

        return response()->json([
            'message' => 'Watch history retrieved successfully',
            'data' => $history,
        ]);
    }

    /**
     * Remove a watch history entry.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            $history = WatchHistory::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$history) {
                return response()->json([
                    'message' => 'Watch history entry not found',
                ], 404);
            }

            $history->delete();

            return response()->json([
                'message' => 'Watch history entry deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete watch history entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all watch history for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->watchHistory()->delete();

            return response()->json([
                'message' => 'All watch history cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear watch history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch update watch progress for multiple videos.
     * Optimized for performance with single database transaction.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'updates' => ['required', 'array', 'min:1', 'max:50'],
                'updates.*.video_id' => ['required', 'integer', 'exists:videos,id'],
                'updates.*.progress' => ['nullable', 'integer', 'min:0'],
                'updates.*.completed' => ['nullable', 'boolean'],
            ]);

            $user = $request->user();
            $updates = $validated['updates'];
            $results = [];

            // Use database transaction for atomicity
            DB::transaction(function () use ($user, $updates, &$results) {
                foreach ($updates as $update) {
                    $history = WatchHistory::updateOrCreate(
                        ['user_id' => $user->id, 'video_id' => $update['video_id']],
                        [
                            'progress' => $update['progress'] ?? 0,
                            'completed' => $update['completed'] ?? false,
                        ]
                    );
                    $results[] = $history;
                }
            });

            // Clear cache for this user
            Cache::forget("watch_history_{$user->id}");

            return response()->json([
                'message' => 'Batch watch progress updated successfully',
                'data' => $results,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to batch update watch progress',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get continue watching list.
     * Optimized with eager loading to prevent N+1 queries.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function continueWatching(Request $request): JsonResponse
    {
        $user = $request->user();

        $history = $user->watchHistory()
            ->incomplete()
            ->with(['video' => function ($query) {
                $query->with(['user:id,name,avatar', 'category:id,name,slug']);
            }])
            ->mostRecent()
            ->limit(10)
            ->get();

        return response()->json([
            'message' => 'Continue watching list retrieved successfully',
            'data' => $history,
        ]);
    }
}
