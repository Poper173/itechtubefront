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
        Schema::table('users', function (Blueprint $table) {
            // Channel profile fields
            $table->string('channel_name', 100)->nullable()->after('name');
            $table->text('channel_description')->nullable()->after('channel_name');
            $table->string('channel_banner', 255)->nullable()->after('channel_description');
            $table->string('stream_key', 100)->nullable()->after('channel_banner');
            $table->enum('stream_status', ['offline', 'live'])->default('offline')->after('stream_key');
            $table->timestamp('stream_started_at')->nullable()->after('stream_status');
            $table->integer('stream_viewers')->default(0)->after('stream_started_at');
            $table->string('stream_title', 255)->nullable()->after('stream_viewers');
            $table->json('stream_settings')->nullable()->after('stream_title');

            // Channel statistics
            $table->unsignedBigInteger('total_views')->default(0)->after('stream_settings');
            $table->unsignedInteger('total_subscribers')->default(0)->after('total_views');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'channel_name',
                'channel_description',
                'channel_banner',
                'stream_key',
                'stream_status',
                'stream_started_at',
                'stream_viewers',
                'stream_title',
                'stream_settings',
                'total_views',
                'total_subscribers',
            ]);
        });
    }
};

