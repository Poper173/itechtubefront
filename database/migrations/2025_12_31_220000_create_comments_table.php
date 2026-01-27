<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Comments table for YouTube-style nested comments.
     * Supports replies and sorting by newest/popular.
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // Comment content
            $table->text('content');

            // Foreign keys
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('video_id')->constrained()->onDelete('cascade');

            // Self-referential relationship for nested replies
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');

            // Moderation
            $table->boolean('is_approved')->default(true);

            // Timestamps
            $table->timestamps();

            // Indexes for performance
            $table->index(['video_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['parent_id']);
        });

        // Create comment_likes table for YouTube-style comment likes
        Schema::create('comment_likes', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('comment_id')->constrained()->onDelete('cascade');

            // Like/Unlike tracking
            $table->boolean('is_like')->default(true);

            // Unique constraint to prevent duplicate likes
            $table->unique(['user_id', 'comment_id']);

            $table->timestamps();

            // Indexes
            $table->index(['comment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_likes');
        Schema::dropIfExists('comments');
    }
};

