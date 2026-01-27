<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Watch history table tracks user's video viewing progress.
     * Each entry records how far a user has watched a specific video.
     */
    public function up(): void
    {
        Schema::create('watch_history', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('video_id')->constrained()->onDelete('cascade');

            // Watch progress
            $table->unsignedInteger('progress')->default(0); // Position in seconds
            $table->unsignedInteger('duration')->default(0); // Total duration watched
            $table->boolean('completed')->default(false);

            // Timestamps
            $table->timestamps();

            // Prevent duplicate entries for same user-video combination
            $table->unique(['user_id', 'video_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watch_history');
    }
};

