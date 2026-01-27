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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            // Subscriber (the user who follows)
            $table->foreignId('subscriber_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Channel (the user being followed)
            $table->foreignId('channel_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->timestamps();

            // Prevent duplicate subscriptions
            $table->unique(['subscriber_id', 'channel_id'], 'unique_subscription');

            // Index for faster lookups
            $table->index('channel_id');
            $table->index('subscriber_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

