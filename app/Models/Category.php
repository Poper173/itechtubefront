<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Category Model
 *
 * Represents a video category for organization.
 * Includes caching and performance optimizations.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Invalidate cache when a category is created, updated, or deleted
        static::created(function ($category) {
            if (class_exists(\App\Services\CacheService::class)) {
                \App\Services\CacheService::invalidateCategories();
            }
        });

        static::updated(function ($category) {
            if (class_exists(\App\Services\CacheService::class)) {
                \App\Services\CacheService::invalidateCategories();
            }
        });

        static::deleted(function ($category) {
            if (class_exists(\App\Services\CacheService::class)) {
                \App\Services\CacheService::invalidateCategories();
            }
        });
    }

    /**
     * Get the videos in this category.
     *
     * @return HasMany
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Get active videos in this category (optimized query).
     *
     * @return HasMany
     */
    public function activeVideos(): HasMany
    {
        return $this->hasMany(Video::class)->where('status', 'active');
    }

    /**
     * Get the video count for this category.
     * Optimized to use cached count when possible.
     *
     * @return int
     */
    public function getVideoCountAttribute(): int
    {
        // Use cached count for performance
        if (class_exists(\App\Services\CacheService::class)) {
            return \App\Services\CacheService::getVideoCountByCategory($this->id);
        }
        return $this->videos()->count();
    }

    /**
     * Get the count of active videos in this category.
     *
     * @return int
     */
    public function getActiveVideoCountAttribute(): int
    {
        return $this->activeVideos()->count();
    }
}

