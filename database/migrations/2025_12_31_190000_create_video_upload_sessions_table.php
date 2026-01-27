<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('video_upload_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id', 64)->unique();
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type', 100);
            $table->unsignedInteger('chunk_size')->default(10 * 1024 * 1024); // 10MB
            $table->unsignedSmallInteger('total_chunks');
            $table->unsignedSmallInteger('uploaded_chunks')->default(0);
            $table->json('uploaded_chunk_indices')->nullable();
            $table->enum('status', ['pending', 'uploading', 'assembling', 'completed', 'failed', 'expired'])
                ->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['user_id', 'status']);
            $table->index(['session_id']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_upload_sessions');
    }
};

