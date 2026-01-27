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
        // Add watched_at column to watch_histories table (assuming it was manually renamed)
        Schema::table('watch_histories', function (Blueprint $table) {
            $table->timestamp('watched_at')->nullable()->after('completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop watched_at column from watch_histories table
        Schema::table('watch_histories', function (Blueprint $table) {
            $table->dropColumn('watched_at');
        });
    }
};
