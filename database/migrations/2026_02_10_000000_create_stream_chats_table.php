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
        Schema::create('stream_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id'); // Creator's user ID
            $table->unsignedBigInteger('user_id'); // Who sent the message
            $table->text('message');
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            // Indexes for efficient queries
            $table->index(['channel_id', 'created_at']);
            $table->index(['channel_id', 'user_id', 'created_at']);

            // Foreign keys
            $table->foreign('channel_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stream_chats');
    }
};

