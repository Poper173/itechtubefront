<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pivot table for many-to-many relationship between playlists and videos.
     * Includes position for ordering videos within a playlist.
     */
    public function up(): void
    {
        Schema::create('playlist_videos', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('playlist_id')->constrained()->onDelete('cascade');
            $table->foreignId('video_id')->constrained()->onDelete('cascade');

            // Position for ordering videos
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            // Unique constraint to prevent duplicate videos in same playlist
            $table->unique(['playlist_id', 'video_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playlist_videos');
    }
};

