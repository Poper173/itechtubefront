<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CommentLike Model
 *
 * Represents a like/dislike on a comment.
 *
 * @property int $id
 * @property int $user_id
 * @property int $comment_id
 * @property bool $is_like
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CommentLike extends Model
{
    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'comment_id',
        'is_like',
    ];

    /**
     * Cast attributes to specific types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_like' => 'boolean',
        ];
    }

    /**
     * Get the user who liked the comment.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the comment that was liked.
     *
     * @return BelongsTo
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * Toggle like status for a comment.
     *
     * @param int $userId
     * @param int $commentId
     * @param bool $isLike
     * @return array{liked: bool, count: int}
     */
    public static function toggleLike(int $userId, int $commentId, bool $isLike = true): array
    {
        $existing = self::where('user_id', $userId)
            ->where('comment_id', $commentId)
            ->first();

        if ($existing) {
            if ($existing->is_like === $isLike) {
                // Remove the like (toggle off)
                $existing->delete();
                return [
                    'liked' => false,
                    'count' => self::where('comment_id', $commentId)->where('is_like', true)->count(),
                ];
            } else {
                // Update from unlike to like or vice versa
                $existing->update(['is_like' => $isLike]);
                return [
                    'liked' => $isLike,
                    'count' => self::where('comment_id', $commentId)->where('is_like', true)->count(),
                ];
            }
        } else {
            // Create new like
            self::create([
                'user_id' => $userId,
                'comment_id' => $commentId,
                'is_like' => $isLike,
            ]);
            return [
                'liked' => true,
                'count' => self::where('comment_id', $commentId)->where('is_like', true)->count(),
            ];
        }
    }

    /**
     * Check if a user has liked a comment.
     *
     * @param int $userId
     * @param int $commentId
     * @return bool
     */
    public static function hasLiked(int $userId, int $commentId): bool
    {
        return self::where('user_id', $userId)
            ->where('comment_id', $commentId)
            ->where('is_like', true)
            ->exists();
    }

    /**
     * Get like count for a comment.
     *
     * @param int $commentId
     * @return int
     */
    public static function getLikeCount(int $commentId): int
    {
        return self::where('comment_id', $commentId)
            ->where('is_like', true)
            ->count();
    }
}

