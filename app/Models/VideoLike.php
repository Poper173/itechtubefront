<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * VideoLike Model
 *
 * Represents a like on a video by a user.
 * Uses polymorphic relationship to potentially support likes on other entities.
 *
 * @property int $id
 * @property int $user_id
 * @property int $video_id
 * @property \Carbon\Carbon $created_at
 */
class VideoLike extends Model
{
    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'user_id',
        'video_id',
    ];

    /**
     * Get the user who liked the video.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the liked video.
     *
     * @return BelongsTo
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Check if a user has liked a specific video.
     *
     * @param int $userId
     * @param int $videoId
     * @return bool
     */
    public static function hasLiked(int $userId, int $videoId): bool
    {
        return static::where('user_id', $userId)
            ->where('video_id', $videoId)
            ->exists();
    }

    /**
     * Toggle like status for a video.
     *
     * @param int $userId
     * @param int $videoId
     * @return array ['liked' => bool, 'count' => int]
     */
    public static function toggleLike(int $userId, int $videoId): array
    {
        $existingLike = static::where('user_id', $userId)
            ->where('video_id', $videoId)
            ->first();

        if ($existingLike) {
            // Unlike
            $existingLike->delete();
            $liked = false;
        } else {
            // Like
            static::create([
                'user_id' => $userId,
                'video_id' => $videoId,
            ]);
            $liked = true;
        }

        $count = static::where('video_id', $videoId)->count();

        return [
            'liked' => $liked,
            'count' => $count,
        ];
    }

    /**
     * Get like count for a video.
     *
     * @param int $videoId
     * @return int
     */
    public static function getLikeCount(int $videoId): int
    {
        return static::where('video_id', $videoId)->count();
    }
}

