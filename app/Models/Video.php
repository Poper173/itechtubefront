<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Video Model
 *
 * Represents a video in the streaming platform.
 * Each video belongs to a user (uploader) and optionally a category.
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property int $user_id
 * @property int|null $category_id
 * @property string $file_path
 * @property string|null $thumbnail_path
 * @property int $file_size
 * @property int $duration
 * @property int $views_count
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Video extends Model
{
    /**
     * Mass-assignable attributes.
     *
     * These fields can be filled using create() or fill() methods.
     */
    protected $fillable = [
        'title',
        'description',
        'user_id',
        'category_id',
        'file_path',
        'thumbnail_path',
        'file_size',
        'duration',
        'views_count',
        'status',
        'visibility',
    ];

    /**
     * Cast attributes to specific types.
     */
    protected $casts = [
        'file_size' => 'integer',
        'duration' => 'integer',
        'views_count' => 'integer',
    ];

    /**
     * Get the user who uploaded the video.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category of the video.
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get watch history records for this video.
     *
     * @return HasMany
     */
    public function watchHistory(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    /**
     * Get likes for this video.
     *
     * @return HasMany
     */
    public function likes(): HasMany
    {
        return $this->hasMany(VideoLike::class);
    }

    /**
     * Get like count attribute.
     *
     * @return int
     */
    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }

    /**
     * Check if the video is liked by a specific user.
     *
     * @param int $userId
     * @return bool
     */
    public function isLikedBy(int $userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * Get playlists that contain this video.
     * (Many-to-many relationship - we'll add pivot table later)
     *
     * @return HasMany
     */
    public function playlistVideos(): HasMany
    {
        return $this->hasMany(PlaylistVideo::class);
    }

    /**
     * Increment view count.
     *
     * @return void
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Format file size for human readability.
     *
     * @return string
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Format duration as HH:MM:SS.
     *
     * @return string
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Check if video is active and can be streamed.
     *
     * @return bool
     */
    public function isStreamable(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if video is publicly visible.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        // Handle NULL visibility as 'public' for backwards compatibility
        return in_array($this->visibility, ['public', null]);
    }

    /**
     * Check if video is visible to a specific user.
     *
     * @param int|null $userId
     * @return bool
     */
    public function isVisibleTo(?int $userId = null): bool
    {
        // Handle NULL visibility as 'public' for backwards compatibility
        $visibility = $this->visibility ?? 'public';

        // Public videos are visible to everyone
        if ($visibility === 'public') {
            return true;
        }

        // Private videos are only visible to the owner
        if ($visibility === 'private') {
            return $userId && $this->user_id === $userId;
        }

        // Unlisted videos are visible to anyone with the link
        if ($visibility === 'unlisted') {
            return true;
        }

        return false;
    }

    /**
     * Get visibility badge class for UI.
     *
     * @return string
     */
    public function getVisibilityBadgeAttribute(): string
    {
        return match($this->visibility) {
            'public' => 'bg-success',
            'private' => 'bg-warning',
            'unlisted' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    /**
     * Get visibility label for UI.
     *
     * @return string
     */
    public function getVisibilityLabelAttribute(): string
    {
        return match($this->visibility) {
            'public' => 'Public',
            'private' => 'Private',
            'unlisted' => 'Unlisted',
            default => 'Unknown',
        };
    }

    /**
     * Scope a query to only include active videos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\EloquentBuilder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include public videos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\EloquentBuilder
     */
    public function scopePublic($query)
    {
        return $query->where(function ($q) {
            $q->where('visibility', 'public')
              ->orWhereNull('visibility'); // Handle NULL as public for backwards compatibility
        });
    }

    /**
     * Scope a query to include videos visible to a user.
     * Shows public videos + user's own private/unlisted videos.
     *
     * @param \Illuminate\Database\Eloquent\EloquentBuilder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\EloquentBuilder
     */
    public function scopeVisibleTo($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where(function ($sq) {
                $sq->where('visibility', 'public')
                   ->orWhereNull('visibility'); // Handle NULL as public
            })
              ->orWhere(function ($sq) use ($userId) {
                  $sq->where('user_id', $userId)
                     ->whereIn('visibility', ['private', 'unlisted', null]); // Include NULL for own videos
              });
        });
    }

    /**
     * Scope a query to order by most viewed.
     *
     * @param \Illuminate\Database\Eloquent.Builder $query
     * @return \Illuminate\Database\Eloquent.Builder
     */
    public function scopeMostViewed($query)
    {
        return $query->orderByDesc('views_count');
    }

    /**
     * Scope a query to order by newest first.
     *
     * @param \Illuminate\Database\Eloquent.Builder $query
     * @return \Illuminate\Database\Eloquent.Builder
     */
    public function scopeNewest($query)
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Scope a query to search videos by title and description.
     * Uses database index on title column for better performance.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     * @return \Illuminate\Database\Eloquent\EloquentBuilder
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'LIKE', "%{$searchTerm}%")
              ->orWhere('description', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Scope a query to only include videos in specific categories.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|int $categoryIds
     * @return \Illuminate\Database\Eloquent\EloquentBuilder
     */
    public function scopeInCategories($query, $categoryIds)
    {
        if (is_array($categoryIds)) {
            return $query->whereIn('category_id', $categoryIds);
        }
        return $query->where('category_id', $categoryIds);
    }

    /**
     * Scope a query for videos with minimum view count.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $count
     * @return \Illuminate\Database\Eloquent\EloquentBuilder
     */
    public function scopeMinViews($query, int $count)
    {
        return $query->where('views_count', '>=', $count);
    }

    /**
     * Scope a query for recently viewed videos (within specified days).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\EloquentBuilder
     */
    public function scopeViewedWithinDays($query, int $days)
    {
        return $query->where('updated_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query with select optimization to fetch only needed columns.
     * Reduces memory usage and query time when full model isn't needed.
     *
     * @param \Illuminate\Database\Eloquent.Builder $query
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\EloquentBuilder
     */
    public function scopeSelectMinimal($query, array $columns = ['id', 'title', 'thumbnail_path', 'duration'])
    {
        return $query->select($columns);
    }

    /**
     * Scope a query with relationship count optimization.
     * Uses withCount() to avoid N+1 queries for counts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\EloquentBuilder
     */
    public function scopeWithWatchCount($query)
    {
        return $query->withCount('watchHistory as watch_count');
    }

    /**
     * Boot method to add model event listeners for cache invalidation.
     */
    protected static function boot()
    {
        parent::boot();

        // Invalidate cache when a video is created, updated, or deleted
        static::created(function ($video) {
            if (class_exists(\App\Services\CacheService::class)) {
                \App\Services\CacheService::invalidateAllVideos();
            }
        });

        static::updated(function ($video) {
            if (class_exists(\App\Services\CacheService::class)) {
                \App\Services\CacheService::invalidateVideo($video->id);
            }
        });

        static::deleted(function ($video) {
            if (class_exists(\App\Services\CacheService::class)) {
                \App\Services\CacheService::invalidateVideo($video->id);
            }
        });
    }

    /**
     * Get the full streaming URL for this video.
     *
     * @return string|null
     */
    public function getVideoUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return route('api.videos.stream', ['video' => $this->id], false);
    }

    /**
     * Get the full URL for the video file (for direct access via storage).
     *
     * @return string|null
     */
    public function getVideoFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return asset('storage/' . $this->file_path);
    }

    /**
     * Get the full URL for the thumbnail.
     *
     * @return string|null
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_path) {
            return null;
        }

        return asset('storage/' . $this->thumbnail_path);
    }

    /**
     * Get the video as a resource array for API responses.
     * Adds computed URLs and formats data for frontend consumption.
     *
     * @return array
     */
    public function toResourceArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'file_path' => $this->file_path,
            'thumbnail_path' => $this->thumbnail_path,
            'video_url' => $this->video_url,
            'video_file_url' => $this->video_file_url,
            'thumbnail_url' => $this->thumbnail_url,
            'file_size' => $this->file_size,
            'duration' => $this->duration,
            'formatted_duration' => $this->formatted_duration,
            'views_count' => $this->views_count,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
            ] : null,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
        ];
    }
}
