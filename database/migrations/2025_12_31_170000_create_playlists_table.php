<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Playlists table stores user-created video collections.
     * Each playlist belongs to a user and can contain multiple videos.
     */
    public function up(): void
    {
        Schema::create('playlists', function (Blueprint $table) {
            $table->id();

            // Foreign key to user
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Playlist details
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('thumbnail_path')->nullable();

            // Visibility
            $table->boolean('is_public')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playlists');
    }
};

