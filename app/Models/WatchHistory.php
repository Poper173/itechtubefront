<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * WatchHistory Model
 *
 * Tracks user watch history and progress for videos.
 * Each record represents a user's interaction with a specific video.
 * Includes caching and performance optimizations.
 *
 * @property int $id
 * @property int $user_id
 * @property int $video_id
 * @property int $progress
 * @property bool $completed
 * @property \Carbon\Carbon $watched_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WatchHistory extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'user_id',
        'video_id',
        'progress',
        'completed',
        'watched_at',
    ];

    /**
     * Cast attributes to specific types.
     */
    protected $casts = [
        'progress' => 'integer',
        'completed' => 'boolean',
        'watched_at' => 'datetime',
    ];

    /**
     * Default values for attributes.
     */
    protected $attributes = [
        'progress' => 0,
        'completed' => false,
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Invalidate cache when watch history changes
        static::saved(function ($history) {
            if (class_exists(\App\Services\CacheService::class)) {
                \App\Services\CacheService::invalidateUserHistoryCache($history->user_id);
            }
        });

        static::deleted(function ($history) {
            if (class_exists(\App\Services\CacheService::class)) {
                \App\Services\CacheService::invalidateUserHistoryCache($history->user_id);
            }
        });
    }

    /**
     * The user who watched the video.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The video that was watched.
     * Optimized eager loading.
     *
     * @return BelongsTo
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Scope a query to only include incomplete watch history.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where('completed', false);
    }

    /**
     * Scope a query to only include completed watch history.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('completed', true);
    }

    /**
     * Scope a query to order by most recent watch activity.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeMostRecent(Builder $query): Builder
    {
        return $query->orderByDesc('watched_at');
    }

    /**
     * Scope a query to order by least recent watch activity.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeLeastRecent(Builder $query): Builder
    {
        return $query->orderBy('watched_at');
    }

    /**
     * Scope a query to filter by user.
     *
     * @param Builder $query
     * @param int $userId
     * @return Builder
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by video.
     *
     * @param Builder $query
     * @param int $videoId
     * @return Builder
     */
    public function scopeForVideo(Builder $query, int $videoId): Builder
    {
        return $query->where('video_id', $videoId);
    }

    /**
     * Scope for continue watching (incomplete, sorted by recent).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeContinueWatching(Builder $query): Builder
    {
        return $query->incomplete()->mostRecent();
    }

    /**
     * Scope with video relationship already loaded.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithVideo(Builder $query): Builder
    {
        return $query->with(['video' => function ($q) {
            $q->with(['user', 'category']);
        }]);
    }

    /**
     * Update watch progress and check if video is completed.
     *
     * @param int $progress
     * @param int $duration
     * @return void
     */
    public function updateProgress(int $progress, int $duration = 0): void
    {
        $this->progress = $progress;

        // Auto-mark as completed if progress reaches or exceeds video duration
        if ($duration > 0 && $progress >= $duration) {
            $this->completed = true;
        } elseif ($progress >= ($this->video->duration ?? 0)) {
            $this->completed = true;
        }

        $this->watched_at = now();
        $this->save();
    }

    /**
     * Mark this watch history entry as completed.
     *
     * @return void
     */
    public function markAsCompleted(): void
    {
        $this->completed = true;
        $this->watched_at = now();
        $this->save();
    }

    /**
     * Calculate watch percentage.
     *
     * @param int|null $duration
     * @return float
     */
    public function getWatchPercentageAttribute(?int $duration = null): float
    {
        $videoDuration = $duration ?? ($this->video->duration ?? 0);

        if ($videoDuration === 0) {
            return 0;
        }

        return round(($this->progress / $videoDuration) * 100, 2);
    }

    /**
     * Check if this is a recent watch (within specified hours).
     *
     * @param int $hours
     * @return bool
     */
    public function isRecent(int $hours = 24): bool
    {
        return $this->watched_at->isAfter(now()->subHours($hours));
    }

    /**
     * Get remaining time to complete the video.
     *
     * @return int
     */
    public function getRemainingSecondsAttribute(): int
    {
        $duration = $this->video->duration ?? 0;
        return max(0, $duration - $this->progress);
    }
}

