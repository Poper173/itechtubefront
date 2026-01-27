<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Playlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * PlaylistController
 *
 * Handles all playlist-related operations.
 * All operations require authentication.
 * Optimized with eager loading to prevent N+1 queries.
 */
class PlaylistController extends Controller
{
    /**
     * List all playlists for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $playlists = $user->playlists()
            ->withCount('videos')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'message' => 'Playlists retrieved successfully',
            'data' => $playlists,
        ]);
    }

    /**
     * Create a new playlist.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'is_public' => ['nullable', 'boolean'],
            ]);

            $user = $request->user();

            $playlist = $user->playlists()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_public' => $validated['is_public'] ?? false,
            ]);

            return response()->json([
                'message' => 'Playlist created successfully',
                'data' => $playlist->load(['user', 'videosWithRelationships']),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create playlist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single playlist.
     *
     * @param Request $request
     * @param Playlist $playlist
     * @return JsonResponse
     */
    public function show(Request $request, Playlist $playlist): JsonResponse
    {
        $user = $request->user();

        // Check if user has access to this playlist
        if (!$playlist->is_public && $playlist->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to view this playlist',
            ], 403);
        }

        // Use optimized videosWithRelationships to prevent N+1 queries
        return response()->json([
            'message' => 'Playlist retrieved successfully',
            'data' => $playlist->load(['user', 'videosWithRelationships']),
        ]);
    }

    /**
     * Update a playlist.
     *
     * Only the playlist owner can update it.
     *
     * @param Request $request
     * @param Playlist $playlist
     * @return JsonResponse
     */
    public function update(Request $request, Playlist $playlist): JsonResponse
    {
        try {
            // Check ownership
            if ($playlist->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only update your own playlists.',
                ], 403);
            }

            $validated = $request->validate([
                'name' => ['sometimes', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'is_public' => ['nullable', 'boolean'],
            ]);

            $playlist->update($validated);

            return response()->json([
                'message' => 'Playlist updated successfully',
                'data' => $playlist->fresh()->load(['user', 'videosWithRelationships']),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update playlist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a playlist.
     *
     * Only the playlist owner can delete it.
     *
     * @param Request $request
     * @param Playlist $playlist
     * @return JsonResponse
     */
    public function destroy(Request $request, Playlist $playlist): JsonResponse
    {
        try {
            // Check ownership
            if ($playlist->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only delete your own playlists.',
                ], 403);
            }

            $playlist->delete();

            return response()->json([
                'message' => 'Playlist deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete playlist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add a video to a playlist.
     *
     * @param Request $request
     * @param Playlist $playlist
     * @return JsonResponse
     */
    public function addVideo(Request $request, Playlist $playlist): JsonResponse
    {
        try {
            // Check ownership
            if ($playlist->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only add videos to your own playlists.',
                ], 403);
            }

            $validated = $request->validate([
                'video_id' => ['required', 'exists:videos,id'],
            ]);

            // Check if video already exists in playlist
            if ($playlist->videos()->where('video_id', $validated['video_id'])->exists()) {
                return response()->json([
                    'message' => 'Video already exists in this playlist',
                ], 422);
            }

            // Get the next position
            $maxPosition = $playlist->videos()->max('position') ?? -1;
            $position = $maxPosition + 1;

            // Add video to playlist
            $playlist->videos()->attach($validated['video_id'], ['position' => $position]);

            return response()->json([
                'message' => 'Video added to playlist successfully',
                'data' => $playlist->fresh()->load(['user', 'videosWithRelationships']),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add video to playlist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a video from a playlist.
     *
     * @param Request $request
     * @param Playlist $playlist
     * @param int $videoId
     * @return JsonResponse
     */
    public function removeVideo(Request $request, Playlist $playlist, int $videoId): JsonResponse
    {
        try {
            // Check ownership
            if ($playlist->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only remove videos from your own playlists.',
                ], 403);
            }

            $playlist->videos()->detach($videoId);

            return response()->json([
                'message' => 'Video removed from playlist successfully',
                'data' => $playlist->fresh()->load(['user', 'videosWithRelationships']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove video from playlist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder videos in a playlist.
     *
     * @param Request $request
     * @param Playlist $playlist
     * @return JsonResponse
     */
    public function reorderVideos(Request $request, Playlist $playlist): JsonResponse
    {
        try {
            // Check ownership
            if ($playlist->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only reorder videos in your own playlists.',
                ], 403);
            }

            $validated = $request->validate([
                'video_ids' => ['required', 'array'],
                'video_ids.*' => ['integer', 'exists:videos,id'],
            ]);

            // Verify all videos belong to this playlist
            $existingVideoIds = $playlist->videos()->pluck('videos.id')->toArray();
            foreach ($validated['video_ids'] as $videoId) {
                if (!in_array($videoId, $existingVideoIds)) {
                    return response()->json([
                        'message' => 'Video ' . $videoId . ' is not in this playlist',
                    ], 422);
                }
            }

            // Update positions
            foreach ($validated['video_ids'] as $position => $videoId) {
                $playlist->videos()->updateExistingPivot($videoId, ['position' => $position]);
            }

            return response()->json([
                'message' => 'Videos reordered successfully',
                'data' => $playlist->fresh()->load(['user', 'videosWithRelationships']),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reorder videos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

