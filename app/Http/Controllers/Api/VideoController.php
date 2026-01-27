<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Models\Video;
use App\Models\VideoLike;
use App\Models\VideoUploadSession;
use App\Models\VideoViewer;
use App\Models\WatchHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use getID3;

/**
 * VideoController
 *
 * Handles all video-related operations including upload, CRUD, and streaming.
 * All operations require authentication except viewing videos.
 */
class VideoController extends Controller
{
    /**
     * List all videos.
     * Optimized with eager loading and query optimization.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Video::query();
        $user = $request->user();

        // Filter by visibility - public videos for everyone + own videos
        if ($user) {
            $query->visibleTo($user->id);
        } else {
            $query->public();
        }

        // Filter by category if provided
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by status (default to active for guests)
        if (!$user) {
            $query->active();
        } elseif ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Sorting - uses database indexes for performance
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['created_at', 'views_count', 'title'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Pagination with optimized query
        $perPage = min($request->input('per_page', 10), 50);

        // Eager loading prevents N+1 queries
        $videos = $query->with(['user:id,name,avatar', 'category:id,name,slug'])->paginate($perPage);

        return response()->json([
            'message' => 'Videos retrieved successfully',
            'data' => VideoResource::collection($videos),
        ]);
    }

    /**
     * Get videos by category.
     * Supports filtering by category ID or slug (name).
     *
     * @param Request $request
     * @param string $category - Category ID, slug, or name
     * @return JsonResponse
     */
    public function videosByCategory(Request $request, string $category): JsonResponse
    {
        try {
            $query = Video::query();
            $user = $request->user();

            // Filter by visibility - public videos for everyone + own videos
            if ($user) {
                $query->visibleTo($user->id);
            } else {
                $query->public();
            }

            // Determine if category is ID, slug, or name
            if (is_numeric($category)) {
                // It's a category ID
                $query->where('category_id', (int) $category);
            } else {
                // It's a slug or name - try to find by slug first
                $categoryModel = \App\Models\Category::where('slug', $category)
                    ->orWhere('name', 'like', '%' . $category . '%')
                    ->first();

                if (!$categoryModel) {
                    return response()->json([
                        'message' => 'Category not found',
                        'category' => $category,
                    ], 404);
                }

                $query->where('category_id', $categoryModel->id);
            }

            // Filter by status (default to active for guests)
            if (!$user) {
                $query->active();
            } elseif ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $allowedSorts = ['created_at', 'views_count', 'title'];

            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
            }

            // Pagination
            $perPage = min($request->input('per_page', 12), 50);

            // Eager loading prevents N+1 queries
            $videos = $query->with(['user:id,name,avatar', 'category:id,name,slug'])->paginate($perPage);

            // Get category info
            $categoryInfo = null;
            if (is_numeric($category)) {
                $categoryInfo = \App\Models\Category::find((int) $category);
            } else {
                $categoryInfo = \App\Models\Category::where('slug', $category)
                    ->orWhere('name', 'like', '%' . $category . '%')
                    ->first();
            }

            return response()->json([
                'message' => 'Videos retrieved successfully',
                'data' => VideoResource::collection($videos),
                'category' => $categoryInfo ? [
                    'id' => $categoryInfo->id,
                    'name' => $categoryInfo->name,
                    'slug' => $categoryInfo->slug,
                ] : null,
                'meta' => [
                    'current_page' => $videos->currentPage(),
                    'last_page' => $videos->lastPage(),
                    'total' => $videos->total(),
                    'per_page' => $videos->perPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve videos by category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload and create a new video.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'category_id' => ['nullable', 'exists:categories,id'],
                'visibility' => ['nullable', 'in:public,private,unlisted', 'default:public'],
                'video' => ['required', 'file', 'mimes:mp4,mov,avi,mkv,webm', 'max:500000'], // Max 500MB
                'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5000'], // Max 5MB
            ]);

            $user = $request->user();

            // Handle video file upload
            $videoFile = $validated['video'];
            $videoFileName = Str::uuid() . '.' . $videoFile->getClientOriginalExtension();
            $videoPath = $videoFile->storeAs('videos', $videoFileName, 'public');

            // Get video file size
            $fileSize = Storage::disk('public')->size($videoPath);

            // Handle thumbnail upload (optional)
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $thumbnailFile = $validated['thumbnail'];
                $thumbnailFileName = Str::uuid() . '.' . $thumbnailFile->getClientOriginalExtension();
                $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailFileName, 'public');
            }

            // Create video record
            $video = Video::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'user_id' => $user->id,
                'category_id' => $validated['category_id'] ?? null,
                'file_path' => $videoPath,
                'thumbnail_path' => $thumbnailPath,
                'file_size' => $fileSize,
                'duration' => 0, // Will be calculated later or by FFmpeg
                'views_count' => 0,
                'status' => 'active', // Auto-activate for now (could be 'processing')
                'visibility' => $validated['visibility'] ?? 'public',
            ]);

            return response()->json([
                'message' => 'Video uploaded successfully',
                'data' => new VideoResource($video->load(['user', 'category'])),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload video',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single video.
     *
     * @param Request $request
     * @param Video $video
     * @return JsonResponse
     */
    public function show(Request $request, Video $video): JsonResponse
    {
        // Check if video is visible to this user
        $user = $request->user();
        $userId = $user?->id;

        if (!$video->isVisibleTo($userId)) {
            return response()->json([
                'message' => 'Video not found or not accessible',
            ], 404);
        }

        // Increment view count for public videos only if this is a new view
        // Uses VideoViewer table to track unique viewers by IP and/or user_id
        // Handle NULL visibility as 'public' for backwards compatibility
        $visibility = $video->visibility ?? 'public';
        if ($visibility === 'public') {
            $ipAddress = VideoViewer::getClientIp($request);
            $userAgent = $request->userAgent();

            // Record the view and only increment if it's a new unique viewer
            $isNewView = VideoViewer::recordView($video->id, $userId, $ipAddress, $userAgent);

            if ($isNewView) {
                $video->incrementViews();
            }
        }

        return response()->json([
            'message' => 'Video retrieved successfully',
            'data' => new VideoResource($video),
        ]);
    }

    /**
     * Get video data for editing.
     *
     * Only the video owner can edit it.
     *
     * @param Request $request
     * @param Video $video
     * @return JsonResponse
     */
    public function edit(Request $request, Video $video): JsonResponse
    {
        try {
            // Check ownership
            if ($video->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only edit your own videos.',
                ], 403);
            }

            return response()->json([
                'message' => 'Video data retrieved for editing',
                'data' => new VideoResource($video->load(['user', 'category'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get video for editing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a video.
     *
     * Only the video owner can update it.
     * Supports partial updates including title, description, category, and thumbnail.
     *
     * @param Request $request
     * @param Video $video
     * @return JsonResponse
     */
    public function update(Request $request, Video $video): JsonResponse
    {
        try {
            // Check ownership
            if ($video->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only update your own videos.',
                ], 403);
            }

            $validated = $request->validate([
                'title' => ['sometimes', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'category_id' => ['nullable', 'exists:categories,id'],
                'visibility' => ['nullable', 'in:public,private,unlisted'],
                'status' => ['sometimes', 'in:processing,active,inactive'],
                'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5000'],
            ]);

            // Handle thumbnail upload (optional)
            if ($request->hasFile('thumbnail')) {
                // Delete old thumbnail if exists
                if ($video->thumbnail_path && Storage::disk('public')->exists($video->thumbnail_path)) {
                    Storage::disk('public')->delete($video->thumbnail_path);
                }

                $thumbnailFile = $validated['thumbnail'];
                $thumbnailFileName = Str::uuid() . '.' . $thumbnailFile->getClientOriginalExtension();
                $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailFileName, 'public');
                $video->thumbnail_path = $thumbnailPath;
            }

            // Update only provided fields
            if (isset($validated['title'])) {
                $video->title = $validated['title'];
            }
            if (array_key_exists('description', $validated)) {
                $video->description = $validated['description'];
            }
            if (array_key_exists('category_id', $validated)) {
                $video->category_id = $validated['category_id'];
            }
            if (array_key_exists('status', $validated)) {
                $video->status = $validated['status'];
            }
            if (array_key_exists('visibility', $validated)) {
                $video->visibility = $validated['visibility'];
            }

            $video->save();

            return response()->json([
                'message' => 'Video updated successfully',
                'data' => new VideoResource($video->fresh()->load(['user', 'category'])),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update video',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a video.
     *
     * Only the video owner can delete it.
     *
     * @param Request $request
     * @param Video $video
     * @return JsonResponse
     */
    public function destroy(Request $request, Video $video): JsonResponse
    {
        try {
            // Check ownership
            if ($video->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only delete your own videos.',
                ], 403);
            }

            // Delete files from storage
            if (Storage::disk('public')->exists($video->file_path)) {
                Storage::disk('public')->delete($video->file_path);
            }

            if ($video->thumbnail_path && Storage::disk('public')->exists($video->thumbnail_path)) {
                Storage::disk('public')->delete($video->thumbnail_path);
            }

            // Delete video record
            $video->delete();

            return response()->json([
                'message' => 'Video deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete video',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stream a video with progressive download support.
     *
     * Handles HTTP Range requests for efficient video streaming.
     * Supports partial content requests for seeking and buffering.
     * Optimized for large files with minimal overhead.
     *
     * @param Request $request
     * @param Video $video
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\JsonResponse
     */
    public function stream(Request $request, Video $video)
    {
        try {
            // Check if video is streamable
            if (!$video->isStreamable()) {
                return response()->json([
                    'message' => 'Video is not available for streaming',
                ], 403);
            }

            // Check if file exists in storage
            if (!$video->file_path || !Storage::disk('public')->exists($video->file_path)) {
                return response()->json([
                    'message' => 'Video file not found',
                    'file_path' => $video->file_path,
                ], 404);
            }

            // Get the full path
            $fullPath = Storage::disk('public')->path($video->file_path);

            // Verify file exists and is readable
            if (!file_exists($fullPath) || !is_readable($fullPath)) {
                return response()->json([
                    'message' => 'Video file is not accessible',
                ], 404);
            }

            $fileSize = filesize($fullPath);

            // Get authenticated user
            $user = $request->user();
            $userId = $user?->id;

            // Increment view count only if this is a new unique viewer
            // Uses VideoViewer table to track unique viewers by IP and/or user_id
            // Handle NULL visibility as 'public' for backwards compatibility
            $visibility = $video->visibility ?? 'public';
            if ($visibility === 'public') {
                $ipAddress = VideoViewer::getClientIp($request);
                $userAgent = $request->userAgent();

                // Record the view and only increment if it's a new unique viewer
                $isNewView = VideoViewer::recordView($video->id, $userId, $ipAddress, $userAgent);

                if ($isNewView) {
                    $video->incrementViews();
                }
            }

            // Detect MIME type from file extension with fallback to file inspection
            $extension = strtolower(pathinfo($video->file_path, PATHINFO_EXTENSION));
            $mimeTypes = [
                'mp4' => 'video/mp4',
                'mov' => 'video/quicktime',
                'avi' => 'video/x-msvideo',
                'mkv' => 'video/x-matroska',
                'webm' => 'video/webm',
                'flv' => 'video/x-flv',
                'mpeg' => 'video/mpeg',
                'mpg' => 'video/mpeg',
                'wmv' => 'video/x-ms-wmv',
                '3gp' => 'video/3gpp',
            ];

            // Use extension-based MIME type first
            $contentType = $mimeTypes[$extension] ?? null;

            // If no match or unknown extension, try to detect from actual file
            if (!$contentType || $contentType === 'video/mp4') {
                if (function_exists('mime_content_type')) {
                    $detectedType = mime_content_type($fullPath);
                    // Only use if it's a valid video type
                    if (strpos($detectedType, 'video/') === 0) {
                        $contentType = $detectedType;
                    }
                }
                // Final fallback
                $contentType = $contentType ?? 'video/mp4';
            }

            // Handle Range header for progressive download/streaming
            $range = $request->header('Range');

            if ($range) {
                // Parse the Range header: "bytes=start-end"
                $ranges = explode('=', $range);
                if (count($ranges) < 2) {
                    return response()->json(['message' => 'Invalid Range header'], 400);
                }

                $byteRanges = explode(',', $ranges[1]);
                $byteRange = explode('-', $byteRanges[0]);

                $start = isset($byteRange[0]) && $byteRange[0] !== '' ? (int) $byteRange[0] : 0;
                $end = isset($byteRange[1]) && $byteRange[1] !== '' ? (int) $byteRange[1] : $fileSize - 1;

                // Validate range
                if ($start >= $fileSize || $end >= $fileSize || $start > $end) {
                    return response()->make('', 416); // Range Not Satisfiable
                }

                $length = $end - $start + 1;

                return response()->stream(function () use ($fullPath, $start, $end) {
                    $handle = fopen($fullPath, 'rb');
                    if (!$handle) {
                        return;
                    }

                    fseek($handle, $start);
                    $bytesToRead = $end - $start + 1;

                    // Use 1MB chunks for better performance
                    $chunkSize = 1024 * 1024;

                    while ($bytesToRead > 0 && !feof($handle)) {
                        $readSize = min($chunkSize, $bytesToRead);
                        $chunk = fread($handle, $readSize);
                        if ($chunk === false) break;
                        echo $chunk;
                        flush();
                        $bytesToRead -= strlen($chunk);
                    }

                    fclose($handle);
                }, 206, [
                    'Content-Type' => $contentType,
                    'Content-Length' => $length,
                    'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
                    'Accept-Ranges' => 'bytes',
                    'Cache-Control' => 'public, max-age=3600',
                    'Expires' => gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT',
                ]);
            }

            // Return full file if no Range header (for download or full playback)
            return response()->stream(function () use ($fullPath) {
                $handle = fopen($fullPath, 'rb');
                if (!$handle) {
                    return;
                }

                // Use 1MB chunks for better performance
                while (!feof($handle)) {
                    $chunk = fread($handle, 1024 * 1024);
                    if ($chunk === false) break;
                    echo $chunk;
                    flush();
                }
                fclose($handle);
            }, 200, [
                'Content-Type' => $contentType,
                'Content-Length' => $fileSize,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=3600',
                'Expires' => gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to stream video',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get direct URL for video streaming.
     * Returns the full URL to the streaming endpoint.
     *
     * @param Video $video
     * @return JsonResponse
     */
    public function getStreamUrl(Video $video): JsonResponse
    {
        if (!$video->isStreamable()) {
            return response()->json([
                'message' => 'Video is not available',
            ], 403);
        }

        // Generate signed URL for secure streaming (valid for 1 hour)
        $url = route('api.videos.stream', ['video' => $video->id], false);

        return response()->json([
            'message' => 'Stream URL generated',
            'data' => [
                'stream_url' => url($url),
                'video_id' => $video->id,
                'expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get videos uploaded by the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function myVideos(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized - No authenticated user',
            ], 401);
        }

        // Get videos with category relationship, including all statuses for owner
        $videos = $user->videos()
            ->with('category:id,name,slug')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'message' => 'User videos retrieved successfully',
            'data' => VideoResource::collection($videos),
            'meta' => [
                'current_page' => $videos->currentPage(),
                'last_page' => $videos->lastPage(),
                'total' => $videos->total(),
                'per_page' => $videos->perPage(),
            ],
        ]);
    }

    /**
     * Get ALL videos on the platform (for debugging/admin purposes).
     * This helps when videos are uploaded manually via phpMyAdmin.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function allVideos(Request $request): JsonResponse
    {
        $query = Video::query();
        $user = $request->user();

        // Filter by visibility - public videos for everyone + own videos
        if ($user) {
            $query->visibleTo($user->id);
        } else {
            $query->public();
        }

        // Filter by category if provided
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by status (default to active for guests)
        if (!$user) {
            $query->active();
        } elseif ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['created_at', 'views_count', 'title'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = min($request->input('per_page', 10), 50);

        // Eager loading prevents N+1 queries
        $videos = $query->with(['user:id,name,avatar', 'category:id,name,slug'])->paginate($perPage);

        return response()->json([
            'message' => 'All videos retrieved successfully',
            'data' => VideoResource::collection($videos),
        ]);
    }

    /**
     * Search videos by title and description.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'q' => ['required', 'string', 'min:1', 'max:255'],
                'category_id' => ['nullable', 'exists:categories,id'],
                'sort_by' => ['nullable', 'in:created_at,views_count,title'],
                'sort_order' => ['nullable', 'in:asc,desc'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            ]);

            $user = $request->user();
            $query = Video::query();

            // Filter by visibility - public videos for everyone + own videos
            if ($user) {
                $query->visibleTo($user->id);
            } else {
                $query->public()->active();
            }

            // Apply search filter (searches title and description)
            $searchTerm = trim($validated['q']);
            $query->search($searchTerm);

            // Filter by category if provided
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Apply sorting - default to newest first for search results
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->input('per_page', 12), 50);
            $videos = $query->with(['user', 'category'])->paginate($perPage);

            return response()->json([
                'message' => 'Search results retrieved successfully',
                'data' => VideoResource::collection($videos),
                'search_term' => $searchTerm,
                'meta' => [
                    'current_page' => $videos->currentPage(),
                    'last_page' => $videos->lastPage(),
                    'total' => $videos->total(),
                    'per_page' => $videos->perPage(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Search failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search videos by title only (for autocomplete/typeahead).
     * Returns titles only for quick searching.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchByName(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'q' => ['required', 'string', 'min:1', 'max:255'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            ]);

            $user = $request->user();
            $limit = min($request->input('limit', 10), 20);
            $searchTerm = trim($validated['q']);

            $query = Video::query();

            // Filter by visibility - public videos for everyone + own videos
            if ($user) {
                $query->visibleTo($user->id);
            } else {
                $query->public()->active();
            }

            // Search by title only (case-insensitive)
            $query->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orderBy('title', 'asc')
                  ->limit($limit);

            $videos = $query->select(['id', 'title', 'thumbnail_path', 'category_id', 'views_count', 'created_at'])
                          ->get();

            return response()->json([
                'message' => 'Search results',
                'data' => $videos,
                'search_term' => $searchTerm,
                'count' => $videos->count(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Search failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record watch progress for a video.
     * Also handles view count increment on first call.
     * Prevents duplicate views from the same user using VideoViewer tracking.
     *
     * @param Request $request
     * @param Video $video
     * @return JsonResponse
     */
    public function recordWatch(Request $request, Video $video): JsonResponse
    {
        try {
            $validated = $request->validate([
                'progress' => ['nullable', 'integer', 'min:0'],
                'completed' => ['nullable', 'boolean'],
            ]);

            $user = $request->user();
            $userId = $user?->id;
            $progress = $validated['progress'] ?? 0;
            $completed = $validated['completed'] ?? false;

            // Increment view count only if this is the first watch record (progress = 0 and not completed)
            // AND the user hasn't already viewed this video before (using VideoViewer for tracking)
            // Handle NULL visibility as 'public' for backwards compatibility
            if ($progress === 0 && !$completed) {
                $visibility = $video->visibility ?? 'public';
                if ($visibility === 'public') {
                    $ipAddress = VideoViewer::getClientIp($request);
                    $userAgent = $request->userAgent();

                    // Record the view and only increment if it's a new unique viewer
                    $isNewView = VideoViewer::recordView($video->id, $userId, $ipAddress, $userAgent);

                    if ($isNewView) {
                        $video->incrementViews();
                    }
                }
            }

            // Find or create watch history entry
            $history = WatchHistory::updateOrCreate(
                ['user_id' => $userId, 'video_id' => $video->id],
                [
                    'progress' => $progress,
                    'completed' => $completed,
                    'watched_at' => now(),
                ]
            );

            // Get updated view count
            $viewsCount = $video->views_count;

            return response()->json([
                'message' => 'Watch progress recorded successfully',
                'data' => [
                    'history' => $history,
                    'views_count' => $viewsCount,
                    'is_first_view' => $progress === 0 && !$completed,
                ],
            ]);
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
     * Initialize a chunked video upload session.
     *
     * This endpoint starts a resumable upload session and returns a session ID.
     * The client then uploads chunks of the file using this session ID.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initChunkedUpload(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'file_name' => ['required', 'string', 'max:255'],
                'file_size' => ['required', 'integer', 'min:1', 'max:500 * 1024 * 1024'], // Max 500MB
                'mime_type' => ['required', 'string', 'max:100'],
                'chunk_size' => ['nullable', 'integer', 'min:1024', 'max:50 * 1024 * 1024'], // 1KB to 50MB
                'total_chunks' => ['required', 'integer', 'min:1'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'category_id' => ['nullable', 'exists:categories,id'],
                'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5000'],
            ]);

            $user = $request->user();

            // Determine chunk size
            $chunkSize = $validated['chunk_size'] ?? VideoUploadSession::CHUNK_SIZE;

            // Create session
            $session = new VideoUploadSession([
                'user_id' => $user->id,
                'session_id' => Str::uuid()->toString(),
                'file_name' => $validated['file_name'],
                'file_path' => '', // Will be set on completion
                'file_size' => $validated['file_size'],
                'mime_type' => $validated['mime_type'],
                'chunk_size' => $chunkSize,
                'total_chunks' => $validated['total_chunks'],
                'uploaded_chunks' => 0,
                'uploaded_chunk_indices' => [],
                'status' => VideoUploadSession::STATUS_PENDING,
                'expires_at' => now()->addHours(24),
            ]);

            // Handle thumbnail upload
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $thumbnailFile = $validated['thumbnail'];
                $thumbnailFileName = Str::uuid() . '.' . $thumbnailFile->getClientOriginalExtension();
                $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailFileName, 'public');
            }

            $session->save();

            // Create video record in 'processing' status
            $video = Video::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'user_id' => $user->id,
                'category_id' => $validated['category_id'] ?? null,
                'file_path' => '', // Temporary - will be updated on completion
                'thumbnail_path' => $thumbnailPath,
                'file_size' => $validated['file_size'],
                'duration' => 0,
                'views_count' => 0,
                'status' => 'processing',
            ]);

            // Link session to video
            $session->update(['file_path' => $video->id]);

            return response()->json([
                'message' => 'Upload session initialized',
                'data' => [
                    'session_id' => $session->session_id,
                    'video_id' => $video->id,
                    'chunk_size' => $chunkSize,
                    'total_chunks' => $validated['total_chunks'],
                    'upload_url' => route('api.videos.upload.chunk'),
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to initialize upload',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a chunk of a video file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadChunk(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'session_id' => ['required', 'string', 'exists:video_upload_sessions,session_id'],
                'chunk_index' => ['required', 'integer', 'min:0'],
                'chunk' => ['required', 'file', 'max:50 * 1024'], // Max 50MB per chunk
            ]);

            $session = VideoUploadSession::where('session_id', $validated['session_id'])->first();

            // Check session validity
            if (!$session->isActive()) {
                return response()->json([
                    'message' => 'Upload session is expired or invalid',
                    'status' => $session->status,
                ], 400);
            }

            // Check chunk index validity
            if ($validated['chunk_index'] >= $session->total_chunks) {
                return response()->json([
                    'message' => 'Invalid chunk index',
                ], 400);
            }

            // Check if chunk already uploaded (idempotency)
            if ($session->isChunkUploaded($validated['chunk_index'])) {
                return response()->json([
                    'message' => 'Chunk already uploaded',
                    'data' => [
                        'chunk_index' => $validated['chunk_index'],
                        'uploaded_chunks' => $session->uploaded_chunks,
                        'progress' => $session->progress,
                    ],
                ]);
            }

            // Save chunk to temporary storage
            $chunkFile = $validated['chunk'];
            $chunkFileName = $session->session_id . '_' . $validated['chunk_index'] . '.chunk';
            $chunkPath = $chunkFile->storeAs('upload_chunks', $chunkFileName, 'public');

            // Mark chunk as uploaded
            $session->markChunkUploaded($validated['chunk_index']);

            // Check if upload is complete
            $isComplete = $session->allChunksUploaded();

            return response()->json([
                'message' => 'Chunk uploaded successfully',
                'data' => [
                    'session_id' => $session->session_id,
                    'chunk_index' => $validated['chunk_index'],
                    'uploaded_chunks' => $session->uploaded_chunks,
                    'total_chunks' => $session->total_chunks,
                    'progress' => $session->progress,
                    'is_complete' => $isComplete,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload chunk',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete a chunked upload and assemble the video file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function completeChunkedUpload(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'session_id' => ['required', 'string', 'exists:video_upload_sessions,session_id'],
            ]);

            $session = VideoUploadSession::where('session_id', $validated['session_id'])->first();

            // Check if session is valid
            if (!$session->isActive()) {
                return response()->json([
                    'message' => 'Upload session is expired or invalid',
                    'status' => $session->status,
                ], 400);
            }

            // Check if all chunks are uploaded
            if (!$session->allChunksUploaded()) {
                return response()->json([
                    'message' => 'Upload not complete',
                    'missing_chunks' => $session->getMissingChunks(),
                    'progress' => $session->progress,
                ], 400);
            }

            // Mark as assembling
            $session->markAssembling();

            // Get the video record
            $video = Video::find($session->file_path);

            if (!$video) {
                $session->markFailed('Video record not found');
                return response()->json([
                    'message' => 'Video record not found',
                ], 404);
            }

            // Assemble chunks into final file
            $finalFileName = Str::uuid() . '.' . pathinfo($session->file_name, PATHINFO_EXTENSION);
            $finalPath = 'videos/' . $finalFileName;
            $fullPath = Storage::disk('public')->path($finalPath);

            // Ensure directory exists
            Storage::disk('public')->makeDirectory('videos');

            // Assemble chunks
            $handle = fopen($fullPath, 'wb');

            for ($i = 0; $i < $session->total_chunks; $i++) {
                $chunkPath = Storage::disk('public')->path('upload_chunks/' . $session->session_id . '_' . $i . '.chunk');

                if (!file_exists($chunkPath)) {
                    $session->markFailed('Chunk file not found: ' . $i);
                    fclose($handle);
                    return response()->json([
                        'message' => 'Chunk file not found',
                        'chunk_index' => $i,
                    ], 500);
                }

                $chunkContent = file_get_contents($chunkPath);
                fwrite($handle, $chunkContent);

                // Delete chunk file
                unlink($chunkPath);
            }

            fclose($handle);

            // Update video with final path
            $video->update([
                'file_path' => $finalPath,
                'status' => 'active',
            ]);

            // Mark session as completed
            $session->markCompleted();

            // Clean up session data
            $session->cleanup();

            return response()->json([
                'message' => 'Video uploaded successfully',
                'data' => new VideoResource($video->load(['user', 'category'])),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to complete upload',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upload session status.
     *
     * @param Request $request
     * @param string $sessionId
     * @return JsonResponse
     */
    public function uploadStatus(Request $request, string $sessionId): JsonResponse
    {
        $session = VideoUploadSession::where('session_id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$session) {
            return response()->json([
                'message' => 'Session not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Session status',
            'data' => [
                'session_id' => $session->session_id,
                'file_name' => $session->file_name,
                'file_size' => $session->file_size,
                'uploaded_chunks' => $session->uploaded_chunks,
                'total_chunks' => $session->total_chunks,
                'progress' => $session->progress,
                'status' => $session->status,
                'missing_chunks' => $session->getMissingChunks(),
                'expires_at' => $session->expires_at,
            ],
        ]);
    }

    /**
     * Abort an upload session.
     *
     * @param Request $request
     * @param string $sessionId
     * @return JsonResponse
     */
    public function abortUpload(Request $request, string $sessionId): JsonResponse
    {
        $session = VideoUploadSession::where('session_id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$session) {
            return response()->json([
                'message' => 'Session not found',
            ], 404);
        }

        // Clean up files
        $session->cleanup();

        // Delete session
        $session->delete();

        // Update video status if exists
        if ($session->file_path && is_numeric($session->file_path)) {
            Video::where('id', $session->file_path)->update(['status' => 'failed']);
        }

        return response()->json([
            'message' => 'Upload aborted',
        ]);
    }

    /**
     * Upload a video from server filesystem.
     * This endpoint allows uploading videos that are already on the server.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadFromServer(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'category_id' => ['nullable', 'exists:categories,id'],
                'file_path' => ['required', 'string', 'max:500'],
                'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5000'],
            ]);

            $user = $request->user();

            // Validate that the file exists on the server
            $serverFilePath = $validated['file_path'];

            // Check if it's an absolute path or relative to storage
            if (file_exists($serverFilePath)) {
                $fullPath = $serverFilePath;
            } else {
                // Try relative to storage/app/public
                $fullPath = storage_path('app/public/' . ltrim($serverFilePath, '/'));
            }

            if (!file_exists($fullPath) || !is_file($fullPath)) {
                return response()->json([
                    'message' => 'Video file not found on server',
                    'file_path' => $validated['file_path'],
                ], 404);
            }

            // Verify it's a video file
            $mimeType = mime_content_type($fullPath);
            $allowedMimeTypes = [
                'video/mp4',
                'video/quicktime',
                'video/x-msvideo',
                'video/x-matroska',
                'video/webm',
                'video/x-flv',
                'video/mpeg',
            ];

            if (!in_array($mimeType, $allowedMimeTypes)) {
                return response()->json([
                    'message' => 'Invalid file type',
                    'detected_type' => $mimeType,
                ], 400);
            }

            // Get file info
            $fileSize = filesize($fullPath);
            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

            // Generate unique filename for storage
            $storageFileName = Str::uuid() . '.' . $extension;
            $storagePath = 'videos/' . $storageFileName;

            // Ensure videos directory exists
            Storage::disk('public')->makeDirectory('videos');

            // Copy file to storage
            if ($fullPath !== Storage::disk('public')->path($storagePath)) {
                copy($fullPath, Storage::disk('public')->path($storagePath));
            }

            // Handle thumbnail upload (optional)
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $thumbnailFile = $validated['thumbnail'];
                $thumbnailFileName = Str::uuid() . '.' . $thumbnailFile->getClientOriginalExtension();
                $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailFileName, 'public');
            }

            // Extract duration from video file
            $duration = $this->extractVideoDuration(Storage::disk('public')->path($storagePath));

            // Create video record
            $video = Video::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'user_id' => $user->id,
                'category_id' => $validated['category_id'] ?? null,
                'file_path' => $storagePath,
                'thumbnail_path' => $thumbnailPath,
                'file_size' => $fileSize,
                'duration' => $duration,
                'views_count' => 0,
                'status' => 'active',
            ]);

            return response()->json([
                'message' => 'Video uploaded successfully from server',
                'data' => new VideoResource($video->load(['user', 'category'])),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload video from server',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get video streaming information.
     * Returns metadata about the video for streaming clients.
     *
     * @param Video $video
     * @return JsonResponse
     */
    public function getStreamInfo(Video $video): JsonResponse
    {
        if (!$video->isStreamable()) {
            return response()->json([
                'message' => 'Video is not available for streaming',
            ], 403);
        }

        // Check if file exists
        if (!$video->file_path || !Storage::disk('public')->exists($video->file_path)) {
            return response()->json([
                'message' => 'Video file not found',
            ], 404);
        }

        $fullPath = Storage::disk('public')->path($video->file_path);
        $fileSize = filesize($fullPath);

        // Get video metadata
        $extension = strtolower(pathinfo($video->file_path, PATHINFO_EXTENSION));
        $mimeTypes = [
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska',
            'webm' => 'video/webm',
            'flv' => 'video/x-flv',
            'mpeg' => 'video/mpeg',
        ];

        return response()->json([
            'message' => 'Video stream info retrieved',
            'data' => [
                'video_id' => $video->id,
                'title' => $video->title,
                'mime_type' => $mimeTypes[$extension] ?? 'video/mp4',
                'file_size' => $fileSize,
                'file_size_formatted' => $this->formatBytes($fileSize),
                'duration' => $video->duration,
                'duration_formatted' => $video->formatted_duration,
                'stream_url' => route('api.videos.stream', ['video' => $video->id]),
                'requires_range_header' => true,
                'supported_operations' => [
                    'play',
                    'pause',
                    'seek',
                    'speed_change',
                ],
            ],
        ]);
    }

    /**
     * List available videos on server storage.
     * Scans the storage directory for video files.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listServerVideos(Request $request): JsonResponse
    {
        try {
            $storagePath = storage_path('app/public/videos');

            if (!is_dir($storagePath)) {
                return response()->json([
                    'message' => 'No videos directory found on server',
                    'data' => [],
                ]);
            }

            $videos = [];
            $extensions = ['mp4', 'mov', 'avi', 'mkv', 'webm', 'flv', 'mpeg'];

            $files = scandir($storagePath);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;

                $fullPath = $storagePath . '/' . $file;
                if (!is_file($fullPath)) continue;

                $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                if (!in_array($extension, $extensions)) continue;

                $fileSize = filesize($fullPath);

                // Check if this video is already in database
                $relativePath = 'videos/' . $file;
                $existingVideo = Video::where('file_path', $relativePath)->first();

                $videos[] = [
                    'file_name' => $file,
                    'file_path' => $relativePath,
                    'file_size' => $fileSize,
                    'file_size_formatted' => $this->formatBytes($fileSize),
                    'exists_in_database' => $existingVideo !== null,
                    'video_id' => $existingVideo?->id,
                    'title' => $existingVideo?->title,
                ];
            }

            return response()->json([
                'message' => 'Server videos retrieved',
                'data' => [
                    'videos' => $videos,
                    'total_count' => count($videos),
                    'storage_path' => $storagePath,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to list server videos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import a video from server storage to database.
     * Quick import for existing server files.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function importFromServer(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'file_path' => ['required', 'string', 'max:500'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'category_id' => ['nullable', 'exists:categories,id'],
            ]);

            $user = $request->user();
            $filePath = $validated['file_path'];

            // Resolve full path
            if (file_exists($filePath)) {
                $fullPath = $filePath;
            } else {
                $fullPath = storage_path('app/public/' . ltrim($filePath, '/'));
            }

            if (!file_exists($fullPath) || !is_file($fullPath)) {
                return response()->json([
                    'message' => 'Video file not found on server',
                    'file_path' => $filePath,
                ], 404);
            }

            // Check if video already exists in database
            $relativePath = 'videos/' . basename($fullPath);
            $existingVideo = Video::where('file_path', $relativePath)->first();

            if ($existingVideo) {
                return response()->json([
                    'message' => 'Video already exists in database',
                    'data' => $existingVideo->load(['user', 'category']),
                ], 200);
            }

            // Get file info
            $fileSize = filesize($fullPath);
            $duration = $this->extractVideoDuration($fullPath);

            // Create video record
            $video = Video::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'user_id' => $user->id,
                'category_id' => $validated['category_id'] ?? null,
                'file_path' => $relativePath,
                'thumbnail_path' => null,
                'file_size' => $fileSize,
                'duration' => $duration,
                'views_count' => 0,
                'status' => 'active',
            ]);

            return response()->json([
                'message' => 'Video imported successfully',
                'data' => new VideoResource($video->load(['user', 'category'])),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to import video',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract video duration from file.
     * Uses getid3 library or falls back to file size estimation.
     *
     * @param string $filePath
     * @return int
     */
    private function extractVideoDuration(string $filePath): int
    {
        // Try using getid3 if available
        if (function_exists('getid3_analyze')) {
            try {
                $getID3 = new \getID3();
                $fileInfo = $getID3->analyze($filePath);
                if (isset($fileInfo['playtime_seconds'])) {
                    return (int) $fileInfo['playtime_seconds'];
                }
            } catch (\Exception $e) {
                // Fall back to default
            }
        }

        // Try using FFprobe if available
        if (function_exists('shell_exec')) {
            $ffprobe = trim(shell_exec('which ffprobe'));
            if ($ffprobe && file_exists($ffprobe)) {
                $cmd = sprintf(
                    '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>/dev/null',
                    escapeshellarg($ffprobe),
                    escapeshellarg($filePath)
                );
                $output = trim(shell_exec($cmd));
                if (is_numeric($output)) {
                    return (int) (float) $output;
                }
            }
        }

        // Default fallback - 0 (duration will be calculated later)
        return 0;
    }

    /**
     * Format bytes to human readable string.
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Toggle like status for a video.
     *
     * @param Request $request
     * @param Video $video
     * @return JsonResponse
     */
    public function toggleLike(Request $request, Video $video): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Authentication required',
                ], 401);
            }

            $result = VideoLike::toggleLike($user->id, $video->id);

            return response()->json([
                'message' => $result['liked'] ? 'Video liked' : 'Video unliked',
                'data' => [
                    'liked' => $result['liked'],
                    'likes_count' => $result['count'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to toggle like',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get like status and count for a video.
     *
     * @param Request $request
     * @param Video $video
     * @return JsonResponse
     */
    public function getLikeStatus(Request $request, Video $video): JsonResponse
    {
        try {
            $user = $request->user();
            $isLiked = false;
            $likesCount = VideoLike::getLikeCount($video->id);

            if ($user) {
                $isLiked = VideoLike::hasLiked($user->id, $video->id);
            }

            return response()->json([
                'message' => 'Like status retrieved',
                'data' => [
                    'likes_count' => $likesCount,
                    'is_liked' => $isLiked,
                    'is_authenticated' => !!$user,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get like status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of users who liked a video.
     *
     * @param Request $request
     * @param Video $video
     * @return JsonResponse
     */
    public function getLikes(Request $request, Video $video): JsonResponse
    {
        try {
            $perPage = min($request->input('per_page', 20), 50);

            $likes = $video->likes()
                ->with('user:id,name,avatar')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'message' => 'Likes retrieved',
                'data' => [
                    'likes' => $likes->items(),
                    'total' => $likes->total(),
                    'current_page' => $likes->currentPage(),
                    'last_page' => $likes->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get likes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all videos liked by the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function likedVideos(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Authentication required',
                ], 401);
            }

            $perPage = min($request->input('per_page', 12), 50);

            // Get user's liked videos with pagination
            $likedVideos = $user->likedVideos()
                ->with(['user:id,name,avatar', 'category:id,name,slug'])
                ->orderByDesc('video_likes.created_at')
                ->paginate($perPage);

            return response()->json([
                'message' => 'Liked videos retrieved successfully',
                'data' => VideoResource::collection($likedVideos),
                'meta' => [
                    'current_page' => $likedVideos->currentPage(),
                    'last_page' => $likedVideos->lastPage(),
                    'total' => $likedVideos->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get liked videos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a video file.
     * Only authenticated users can download videos.
     * Users can download their own videos or any public video.
     *
     * @param Request $request
     * @param Video $video
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
     */
    public function download(Request $request, Video $video)
    {
        try {
            $user = $request->user();

            // Authentication required for download
            if (!$user) {
                return response()->json([
                    'message' => 'Authentication required to download videos',
                ], 401);
            }

            // Check if user can access this video
            if (!$video->isVisibleTo($user->id)) {
                return response()->json([
                    'message' => 'Video not found or not accessible',
                ], 404);
            }

            // Check if file exists in storage
            if (!$video->file_path || !Storage::disk('public')->exists($video->file_path)) {
                return response()->json([
                    'message' => 'Video file not found',
                ], 404);
            }

            // Get the full path
            $fullPath = Storage::disk('public')->path($video->file_path);

            // Verify file exists and is readable
            if (!file_exists($fullPath) || !is_readable($fullPath)) {
                return response()->json([
                    'message' => 'Video file is not accessible',
                ], 404);
            }

            $fileSize = filesize($fullPath);

            // Get the original filename or generate one from title
            $originalName = $video->title ?: 'video_' . $video->id;
            // Sanitize filename - remove special characters
            $originalName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
            $extension = strtolower(pathinfo($video->file_path, PATHINFO_EXTENSION));
            $fileName = $originalName . '.' . $extension;

            // Detect MIME type
            $extension = strtolower(pathinfo($video->file_path, PATHINFO_EXTENSION));
            $mimeTypes = [
                'mp4' => 'video/mp4',
                'mov' => 'video/quicktime',
                'avi' => 'video/x-msvideo',
                'mkv' => 'video/x-matroska',
                'webm' => 'video/webm',
                'flv' => 'video/x-flv',
                'mpeg' => 'video/mpeg',
                'mpg' => 'video/mpeg',
            ];

            $contentType = $mimeTypes[$extension] ?? 'video/mp4';

            // For small files, return as download attachment
            if ($fileSize <= 100 * 1024 * 1024) { // 100MB threshold
                return response()->download(
                    $fullPath,
                    $fileName,
                    [
                        'Content-Type' => $contentType,
                        'Content-Length' => $fileSize,
                        'Cache-Control' => 'no-cache, must-revalidate',
                        'Pragma' => 'no-cache',
                    ]
                );
            }

            // For large files, stream the download in chunks
            return response()->stream(function () use ($fullPath) {
                $handle = fopen($fullPath, 'rb');
                if (!$handle) {
                    return;
                }

                // Use 1MB chunks for better performance
                while (!feof($handle)) {
                    $chunk = fread($handle, 1024 * 1024);
                    if ($chunk === false) break;
                    echo $chunk;
                    flush();
                }
                fclose($handle);
            }, 200, [
                'Content-Type' => $contentType,
                'Content-Length' => $fileSize,
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Accept-Ranges' => 'none',
                'Cache-Control' => 'no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to download video',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get download information for a video.
     * Returns metadata about the video that can be downloaded.
     *
     * @param Request $request
     * @param Video $video
     * @return JsonResponse
     */
    public function getDownloadInfo(Request $request, Video $video): JsonResponse
    {
        try {
            $user = $request->user();

            // Authentication required for download info
            if (!$user) {
                return response()->json([
                    'message' => 'Authentication required',
                ], 401);
            }

            // Check if user can access this video
            if (!$video->isVisibleTo($user->id)) {
                return response()->json([
                    'message' => 'Video not found or not accessible',
                ], 404);
            }

            // Check if file exists
            if (!$video->file_path || !Storage::disk('public')->exists($video->file_path)) {
                return response()->json([
                    'message' => 'Video file not found',
                    'available' => false,
                ], 404);
            }

            $fullPath = Storage::disk('public')->path($video->file_path);
            $fileSize = filesize($fullPath);

            // Generate filename
            $originalName = $video->title ?: 'video_' . $video->id;
            $originalName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
            $extension = strtolower(pathinfo($video->file_path, PATHINFO_EXTENSION));
            $fileName = $originalName . '.' . $extension;

            return response()->json([
                'message' => 'Download info retrieved',
                'data' => [
                    'video_id' => $video->id,
                    'title' => $video->title,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'file_size_formatted' => $this->formatBytes($fileSize),
                    'download_url' => route('api.videos.download', ['video' => $video->id]),
                    'available' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get download info',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

