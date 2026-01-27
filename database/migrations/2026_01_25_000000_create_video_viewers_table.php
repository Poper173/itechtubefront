<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This table tracks unique video viewers by IP address and/or user_id
     * to prevent duplicate view counting when users watch videos as guests
     * and then logged in users.
     */
    public function up(): void
    {
        Schema::create('video_viewers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('video_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45); // IPv6 compatible
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('viewed_at')->useCurrent();

            // Indexes for fast lookups
            $table->index(['video_id', 'user_id']);
            $table->index(['video_id', 'ip_address']);
            $table->index(['video_id', 'user_id', 'ip_address'], 'video_user_ip_index');

            // Prevent duplicate records for same viewer on same video
            $table->unique(['video_id', 'user_id'], 'video_user_unique')
                ->where('user_id', '!=', null);
            $table->unique(['video_id', 'ip_address'], 'video_ip_unique')
                ->whereNull('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_viewers');
    }
};

