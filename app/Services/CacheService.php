<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Video;
use App\Models\Category;

/**
 * CacheService - Step 14.4: Caching Strategies
 *
 * This service provides caching for frequently accessed data
 * to reduce database queries and improve API response times.
 *
 * Cache Keys:
 * - categories.list: All categories (rarely changes)
 * - videos.featured: Featured/popular videos
 * - videos.recent: Recently uploaded videos
 * - video.{id}: Individual video data
 * - playlist.{id}: Individual playlist data
 * - user.{id}.history: User watch history
 *
 * Cache Invalidation:
 * - Categories: Invalidate when a category is created/updated/deleted
 * - Videos: Invalidate when video is created/updated/deleted
 * - Playlists: Invalidate when playlist is modified
 * - History: Invalidate on watch progress updates
 */
class CacheService
{
    /**
     * Cache TTL configurations (in seconds).
     */
    const CACHE_TTL_CATEGORIES = 3600;      // 1 hour - categories rarely change
    const CACHE_TTL_VIDEOS = 900;           // 15 minutes - videos change moderately
    const CACHE_TTL_FEATURED_VIDEOS = 1800; // 30 minutes - featured videos
    const CACHE_TTL_RECENT_VIDEOS = 300;    // 5 minutes - recent videos change often
    const CACHE_TTL_VIDEO = 1800;           // 30 minutes - individual video
    const CACHE_TTL_PLAYLIST = 600;         // 10 minutes - playlist data
    const CACHE_TTL_HISTORY = 60;           // 1 minute - watch history changes frequently

    /**
     * Get cached categories or fetch and cache them.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getCategories(): \Illuminate\Support\Collection
    {
        return Cache::remember('categories.list', self::CACHE_TTL_CATEGORIES, function () {
            return Category::orderBy('name')->get();
        });
    }

    /**
     * Invalidate categories cache.
     */
    public static function invalidateCategories(): void
    {
        Cache::forget('categories.list');
    }

    /**
     * Get featured videos (most viewed) with caching.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getFeaturedVideos(int $limit = 10): \Illuminate\Support\Collection
    {
        return Cache::remember(
            "videos.featured.{$limit}",
            self::CACHE_TTL_FEATURED_VIDEOS,
            function () use ($limit) {
                return Video::active()
                    ->with(['user', 'category'])
                    ->mostViewed()
                    ->limit($limit)
                    ->get();
            }
        );
    }

    /**
     * Get recent videos with caching.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getRecentVideos(int $limit = 10): \Illuminate\Support\Collection
    {
        return Cache::remember(
            "videos.recent.{$limit}",
            self::CACHE_TTL_RECENT_VIDEOS,
            function () use ($limit) {
                return Video::active()
                    ->with(['user', 'category'])
                    ->newest()
                    ->limit($limit)
                    ->get();
            }
        );
    }

    /**
     * Get cached video or fetch and cache it.
     *
     * @param int $id
     * @return Video|null
     */
    public static function getVideo(int $id): ?Video
    {
        return Cache::remember(
            "video.{$id}",
            self::CACHE_TTL_VIDEO,
            function () use ($id) {
                return Video::with(['user', 'category'])->find($id);
            }
        );
    }

    /**
     * Invalidate video cache.
     *
     * @param int $id
     */
    public static function invalidateVideo(int $id): void
    {
        Cache::forget("video.{$id}");
        // Note: Using Cache::flush() instead of Cache::tags() because the database
        // cache driver doesn't support tagging. For production with Redis/Memcached,
        // cache tags can be used for more granular cache invalidation.
    }

    /**
     * Invalidate all video-related caches.
     */
    public static function invalidateAllVideos(): void
    {
        // Note: Using Cache::flush() instead of Cache::tags() because the database
        // cache driver doesn't support tagging. For production with Redis/Memcached,
        // cache tags can be used for more granular cache invalidation.
        Cache::flush();
    }

    /**
     * Get cached video count by category.
     *
     * @param int $categoryId
     * @return int
     */
    public static function getVideoCountByCategory(int $categoryId): int
    {
        return Cache::remember(
            "category.{$categoryId}.video_count",
            self::CACHE_TTL_VIDEOS,
            function () use ($categoryId) {
                return Video::where('category_id', $categoryId)->active()->count();
            }
        );
    }

    /**
     * Invalidate category video count cache.
     *
     * @param int $categoryId
     */
    public static function invalidateCategoryVideoCount(int $categoryId): void
    {
        Cache::forget("category.{$categoryId}.video_count");
    }

    /**
     * Get videos by category with pagination and caching.
     * Note: Paginated results are not cached due to complexity.
     *
     * @param int $categoryId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getVideosByCategory(int $categoryId, int $perPage = 10)
    {
        // Don't cache paginated results, but use query optimization
        return Video::active()
            ->where('category_id', $categoryId)
            ->with(['user', 'category'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get search results with optimized query (no caching for dynamic searches).
     *
     * @param string $searchTerm
     * @param int|null $categoryId
     * @param string $sortBy
     * @param string $sortOrder
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function searchVideos(
        string $searchTerm,
        ?int $categoryId = null,
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 10
    ) {
        $query = Video::active()->search($searchTerm);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->with(['user', 'category'])
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);
    }

    /**
     * Get user's continue watching list.
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getContinueWatching(int $userId, int $limit = 10): \Illuminate\Support\Collection
    {
        return Cache::remember(
            "user.{$userId}.continue_watching",
            self::CACHE_TTL_HISTORY,
            function () use ($userId, $limit) {
                return DB::table('watch_history')
                    ->where('user_id', $userId)
                    ->where('completed', false)
                    ->orderByDesc('watched_at')
                    ->limit($limit)
                    ->get();
            }
        );
    }

    /**
     * Invalidate user's continue watching cache.
     *
     * @param int $userId
     */
    public static function invalidateContinueWatching(int $userId): void
    {
        Cache::forget("user.{$userId}.continue_watching");
    }

    /**
     * Get user's watch history count.
     *
     * @param int $userId
     * @return int
     */
    public static function getWatchHistoryCount(int $userId): int
    {
        return Cache::remember(
            "user.{$userId}.history_count",
            self::CACHE_TTL_HISTORY,
            function () use ($userId) {
                return DB::table('watch_history')
                    ->where('user_id', $userId)
                    ->count();
            }
        );
    }

    /**
     * Invalidate user's watch history cache.
     *
     * @param int $userId
     */
    public static function invalidateUserHistoryCache(int $userId): void
    {
        Cache::forget("user.{$userId}.continue_watching");
        Cache::forget("user.{$userId}.history_count");
    }

    /**
     * Clear all application caches.
     */
    public static function clearAllCaches(): void
    {
        Cache::flush();
    }

    /**
     * Warm up all caches (run on deployment or manually).
     */
    public static function warmUpCaches(): void
    {
        // Pre-populate frequently accessed caches
        self::getCategories();
        self::getFeaturedVideos(10);
        self::getRecentVideos(10);

        // Cache first page of videos by category
        Category::chunkById(50, function ($categories) {
            foreach ($categories as $category) {
                self::getVideoCountByCategory($category->id);
            }
        });
    }

    /**
     * Get cache statistics for monitoring.
     *
     * @return array
     */
    public static function getCacheStats(): array
    {
        return [
            'driver' => config('cache.default'),
            'stores' => array_keys(config('cache.stores')),
            'ttl_categories' => self::CACHE_TTL_CATEGORIES,
            'ttl_videos' => self::CACHE_TTL_VIDEOS,
            'ttl_featured' => self::CACHE_TTL_FEATURED_VIDEOS,
        ];
    }
}

