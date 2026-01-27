<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Performance Optimization Migration - Step 14.1
 *
 * This migration adds database indexes to improve query performance.
 * Indexes are critical for:
 * - Filtering videos by category/status
 * - Sorting by views_count and created_at
 * - Looking up watch history by user/video
 * - Playlist video ordering
 *
 * Indexes are added on columns that are:
 * - Frequently used in WHERE clauses
 * - Used for sorting (ORDER BY)
 * - Foreign keys (for join optimization)
 * - Used in unique constraints
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Videos table indexes
        Schema::table('videos', function (Blueprint $table) {
            // Index for category + status filtering (used in video listing)
            // Note: May already exist from foreignId, so we check first
            $this->addIndexIfNotExists('videos', ['category_id', 'status'], 'videos_category_id_status_index');

            // Index for user videos + sorting
            $this->addIndexIfNotExists('videos', ['user_id', 'created_at'], 'videos_user_id_created_at_index');

            // Index for sorting by views (most viewed videos)
            $this->addIndexIfNotExists('videos', ['views_count', 'created_at'], 'videos_views_count_created_at_index');

            // Index for search on title (for LIKE queries)
            $this->addIndexIfNotExists('videos', ['title'], 'videos_title_index');
        });

        // Watch history table indexes
        Schema::table('watch_history', function (Blueprint $table) {
            // Index for user history with sorting (most recent first)
            $this->addIndexIfNotExists('watch_history', ['user_id', 'created_at'], 'watch_history_user_id_created_at_index');

            // Index for filtering incomplete/completed
            $this->addIndexIfNotExists('watch_history', ['user_id', 'completed'], 'watch_history_user_id_completed_index');

            // Index for continue watching (incomplete + recent)
            $this->addIndexIfNotExists('watch_history', ['user_id', 'completed', 'created_at'], 'watch_history_user_id_completed_created_at_index');

            // Index for video watch history
            $this->addIndexIfNotExists('watch_history', ['video_id', 'user_id'], 'watch_history_video_id_user_id_index');
        });

        // Playlist videos table indexes
        Schema::table('playlist_videos', function (Blueprint $table) {
            // Index for playlist ordering (most common query)
            $this->addIndexIfNotExists('playlist_videos', ['playlist_id', 'position'], 'playlist_videos_playlist_id_position_index');

            // Index for checking video existence in playlist
            $this->addIndexIfNotExists('playlist_videos', ['playlist_id', 'video_id'], 'playlist_videos_playlist_id_video_id_index');

            // Index for user's playlists
            $this->addIndexIfNotExists('playlist_videos', ['playlist_id', 'video_id', 'position'], 'playlist_videos_playlist_id_video_id_position_index');
        });

        // Playlists table indexes
        Schema::table('playlists', function (Blueprint $table) {
            // Index for user playlists with sorting
            $this->addIndexIfNotExists('playlists', ['user_id', 'created_at'], 'playlists_user_id_created_at_index');

            // Index for public playlists
            $this->addIndexIfNotExists('playlists', ['is_public', 'created_at'], 'playlists_is_public_created_at_index');
        });

        // Categories table indexes
        Schema::table('categories', function (Blueprint $table) {
            // Index for slug-based lookups
            $this->addIndexIfNotExists('categories', ['slug'], 'categories_slug_index');
        });
    }

    /**
     * Helper method to add index if it doesn't exist.
     */
    protected function addIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        // Check if index already exists
        $exists = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'");

        if (empty($exists)) {
            // Create the index manually with the specific name
            $columnList = implode('`, `', $columns);
            DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$columnList}`)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from videos table
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'status']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['views_count', 'created_at']);
            $table->dropIndex(['title']);
        });

        // Drop indexes from watch_history table
        Schema::table('watch_history', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['user_id', 'completed']);
            $table->dropIndex(['user_id', 'completed', 'created_at']);
            $table->dropIndex(['video_id', 'user_id']);
        });

        // Drop indexes from playlist_videos table
        Schema::table('playlist_videos', function (Blueprint $table) {
            $table->dropIndex(['playlist_id', 'position']);
            $table->dropIndex(['playlist_id', 'video_id']);
            $table->dropIndex(['playlist_id', 'video_id', 'position']);
        });

        // Drop indexes from playlists table
        Schema::table('playlists', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['is_public', 'created_at']);
        });

        // Drop index from categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['slug']);
        });
    }
};

