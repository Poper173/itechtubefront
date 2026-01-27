<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Playlist Model
 *
 * Represents a user's playlist containing videos.
 * Includes caching and performance optimizations.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_public
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Playlist extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_public',
    ];

    /**
     * Cast attributes to specific types.
     */
    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Invalidate cache when a playlist is created, updated, or deleted
        static::created(function ($playlist) {
            if (class_exists(\App\Services\CacheService::class)) {
                \App\Services\CacheService::invalidateVideo($playlist->id);
            }
        });

        static::updated(function ($playlist) {
            if (class_exists(\App\Services\CacheService::class)) {
                \App\Services\CacheService::invalidateVideo($playlist->id);
            }
        });

        static::deleted(function ($playlist) {
            if (class_exists(\App\Services\CacheService::class)) {
                \App\Services\CacheService::invalidateVideo($playlist->id);
            }
        });
    }

    /**
     * Get the user who created this playlist.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the videos in this playlist.
     * Optimized with pivot table access for better performance.
     *
     * @return BelongsToMany
     */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'playlist_videos')
            ->withPivot('id', 'position')
            ->orderBy('position');
    }

    /**
     * Get videos with eager loading of relationships.
     * Prevents N+1 queries when loading playlist videos.
     *
     * @return BelongsToMany
     */
    public function videosWithRelationships(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'playlist_videos')
            ->withPivot('id', 'position')
            ->with(['user', 'category'])
            ->orderBy('position');
    }

    /**
     * Get the playlist video entries (pivot records).
     *
     * @return HasMany
     */
    public function playlistVideos(): HasMany
    {
        return $this->hasMany(PlaylistVideo::class);
    }

    /**
     * Scope a query to only include public playlists.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include private playlists.
     *
     * @param \Illuminate\Database\Eloquent.Builder $query
     * @return \Illuminate\Database\Eloquent.Builder
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope a query for playlists owned by a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query with video count.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithVideoCount($query)
    {
        return $query->withCount('videos');
    }

    /**
     * Check if this playlist is public.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->is_public;
    }

    /**
     * Get the video count for this playlist.
     * Uses cached count when possible.
     *
     * @return int
     */
    public function getVideoCountAttribute(): int
    {
        return $this->videos()->count();
    }

    /**
     * Get total duration of all videos in playlist.
     *
     * @return int
     */
    public function getTotalDurationAttribute(): int
    {
        return $this->videos()->sum('duration');
    }

    /**
     * Get formatted total duration.
     *
     * @return string
     */
    public function getFormattedTotalDurationAttribute(): string
    {
        $hours = floor($this->total_duration / 3600);
        $minutes = floor(($this->total_duration % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }
}

