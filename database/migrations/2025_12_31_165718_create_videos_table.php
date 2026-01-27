<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Videos table stores all video metadata and file information.
     * Links to users (uploader) and categories for organization.
     */
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();

            // Basic info
            $table->string('title');
            $table->text('description')->nullable();

            // Foreign keys
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');

            // File information
            $table->string('file_path');        // Path to video file
            $table->string('thumbnail_path')->nullable(); // Path to thumbnail
            $table->bigInteger('file_size')->default(0);  // Size in bytes
            $table->unsignedInteger('duration')->default(0); // Duration in seconds

            // Statistics
            $table->BigInteger('views_count')->default(0)->change();

            // Status (processing, active, inactive)
            $table->enum('status', ['processing', 'active', 'inactive'])->default('processing');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
