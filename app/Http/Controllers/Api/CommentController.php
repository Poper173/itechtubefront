<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\CommentLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CommentController
 *
 * Handles all comment-related operations.
 * Supports nested replies and sorting.
 */
class CommentController extends Controller
{
    /**
     * List comments for a video.
     *
     * @param Request $request
     * @param int $videoId
     * @return JsonResponse
     */
    public function index(Request $request, int $videoId): JsonResponse
    {
        $validator = Validator::make(['video_id' => $videoId], [
            'video_id' => ['required', 'exists:videos,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $sortBy = $request->input('sort_by', 'newest'); // newest, oldest, popular
        $perPage = min($request->input('per_page', 20), 50);

        $query = Comment::where('video_id', $videoId)
            ->where('is_approved', true)
            ->whereNull('parent_id') // Only top-level comments
            ->with(['user:id,name,avatar', 'likes']);

        // Apply sorting
        switch ($sortBy) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'popular':
                $query->withCount(['likes as likes_count' => function ($q) {
                    $q->where('is_like', true);
                }])->orderByDesc('likes_count');
                break;
            case 'newest':
            default:
                $query->orderByDesc('created_at');
                break;
        }

        $comments = $query->paginate($perPage);

        // Load replies for each comment (limit to 3 most recent)
        foreach ($comments->items() as $comment) {
            $comment->load(['replies' => function ($q) {
                $q->where('is_approved', true)
                    ->with(['user:id,name,avatar'])
                    ->orderByDesc('created_at')
                    ->limit(3);
            }]);
        }

        return response()->json([
            'message' => 'Comments retrieved successfully',
            'data' => CommentResource::collection($comments),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'total' => $comments->total(),
                'sort_by' => $sortBy,
            ],
        ]);
    }

    /**
     * Store a new comment.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => ['required', 'string', 'min:1', 'max:5000'],
            'video_id' => ['required', 'exists:videos,id'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Authentication required to post comments',
            ], 401);
        }

        // Verify parent comment belongs to same video if provided
        if ($request->parent_id) {
            $parentComment = Comment::find($request->parent_id);
            if (!$parentComment || $parentComment->video_id !== $request->video_id) {
                return response()->json([
                    'message' => 'Invalid parent comment',
                ], 422);
            }
        }

        $comment = Comment::create([
            'content' => $request->content,
            'user_id' => $user->id,
            'video_id' => $request->video_id,
            'parent_id' => $request->parent_id,
            'is_approved' => true, // Auto-approve for now
        ]);

        $comment->load(['user:id,name,avatar']);

        return response()->json([
            'message' => 'Comment posted successfully',
            'data' => new CommentResource($comment),
        ], 201);
    }

    /**
     * Show a single comment with replies.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $comment = Comment::with(['user:id,name,avatar', 'replies.user:id,name,avatar', 'likes'])
            ->find($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Comment retrieved successfully',
            'data' => new CommentResource($comment),
        ]);
    }

    /**
     * Update a comment.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only edit your own comments.',
            ], 403);
        }

        $comment->update(['content' => $request->content]);

        return response()->json([
            'message' => 'Comment updated successfully',
            'data' => new CommentResource($comment->fresh()->load('user:id,name,avatar')),
        ]);
    }

    /**
     * Delete a comment.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        // Allow deletion by owner or video owner
        $video = $comment->video;
        if ($comment->user_id !== $user->id && $video->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to delete this comment',
            ], 403);
        }

        // Delete associated likes
        CommentLike::where('comment_id', $id)->delete();

        // Delete the comment (replies will be cascade deleted)
        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }

    /**
     * Toggle like on a comment.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function toggleLike(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Authentication required',
            ], 401);
        }

        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        $result = CommentLike::toggleLike($user->id, $id, true);

        return response()->json([
            'message' => $result['liked'] ? 'Comment liked' : 'Like removed',
            'data' => [
                'liked' => $result['liked'],
                'likes_count' => $result['count'],
            ],
        ]);
    }

    /**
     * Get replies to a comment.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getReplies(Request $request, int $id): JsonResponse
    {
        $perPage = min($request->input('per_page', 10), 50);

        $replies = Comment::where('parent_id', $id)
            ->where('is_approved', true)
            ->with(['user:id,name,avatar'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'message' => 'Replies retrieved successfully',
            'data' => CommentResource::collection($replies),
            'meta' => [
                'current_page' => $replies->currentPage(),
                'last_page' => $replies->lastPage(),
                'total' => $replies->total(),
            ],
        ]);
    }

    /**
     * Get comment count for a video.
     *
     * @param int $videoId
     * @return JsonResponse
     */
    public function getCount(int $videoId): JsonResponse
    {
        $count = Comment::where('video_id', $videoId)
            ->where('is_approved', true)
            ->count();

        return response()->json([
            'message' => 'Comment count retrieved',
            'data' => [
                'count' => $count,
            ],
        ]);
    }
}
