<?php

namespace Tests\Unit\Models;

use App\Models\WatchHistory;
use App\Models\Video;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

class WatchHistoryModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that watch history can be created with required attributes.
     */
    public function test_watch_history_can_be_created(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();

        $history = WatchHistory::create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'progress' => 60,
            'completed' => false,
        ]);

        $this->assertNotNull($history->id);
        $this->assertEquals($user->id, $history->user_id);
        $this->assertEquals($video->id, $history->video_id);
        $this->assertEquals(60, $history->progress);
        $this->assertFalse($history->completed);
    }

    /**
     * Test watch history belongs to user relationship.
     */
    public function test_watch_history_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();
        $history = WatchHistory::factory()->create(['user_id' => $user->id, 'video_id' => $video->id]);

        $this->assertInstanceOf(User::class, $history->user);
        $this->assertEquals($user->id, $history->user->id);
    }

    /**
     * Test watch history belongs to video relationship.
     */
    public function test_watch_history_belongs_to_video(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();
        $history = WatchHistory::factory()->create(['user_id' => $user->id, 'video_id' => $video->id]);

        $this->assertInstanceOf(Video::class, $history->video);
        $this->assertEquals($video->id, $history->video->id);
    }

    /**
     * Test watch history incomplete scope.
     */
    public function test_watch_history_incomplete_scope(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();

        $incomplete = WatchHistory::factory()->create(['user_id' => $user->id, 'video_id' => $video->id, 'completed' => false]);
        $completed = WatchHistory::factory()->create(['user_id' => $user->id, 'video_id' => $video->id, 'completed' => true]);

        $incompleteHistories = WatchHistory::incomplete()->get();

        $this->assertCount(1, $incompleteHistories);
        $this->assertEquals($incomplete->id, $incompleteHistories->first()->id);
    }

    /**
     * Test watch history completed scope.
     */
    public function test_watch_history_completed_scope(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();

        $incomplete = WatchHistory::factory()->create(['user_id' => $user->id, 'video_id' => $video->id, 'completed' => false]);
        $completed = WatchHistory::factory()->create(['user_id' => $user->id, 'video_id' => $video->id, 'completed' => true]);

        $completedHistories = WatchHistory::completed()->get();

        $this->assertCount(1, $completedHistories);
        $this->assertEquals($completed->id, $completedHistories->first()->id);
    }

    /**
     * Test watch history most recent scope.
     */
    public function test_watch_history_most_recent_scope(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();

        $oldHistory = WatchHistory::factory()->create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'watched_at' => now()->subDay()
        ]);
        $recentHistory = WatchHistory::factory()->create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'watched_at' => now()
        ]);

        $mostRecent = WatchHistory::mostRecent()->first();

        $this->assertEquals($recentHistory->id, $mostRecent->id);
    }

    /**
     * Test watch history updateProgress method.
     */
    public function test_watch_history_update_progress(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create(['duration' => 120]);
        $history = WatchHistory::factory()->create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'progress' => 0
        ]);

        $history->updateProgress(60);

        $this->assertEquals(60, $history->fresh()->progress);
        $this->assertFalse($history->fresh()->completed);
    }

    /**
     * Test watch history auto marks as completed when progress exceeds duration.
     */
    public function test_watch_history_auto_complete(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create(['duration' => 100]);
        $history = WatchHistory::factory()->create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'progress' => 0
        ]);

        $history->updateProgress(150); // Exceeds duration

        $this->assertEquals(150, $history->fresh()->progress);
        $this->assertTrue($history->fresh()->completed);
    }

    /**
     * Test watch history markAsCompleted method.
     */
    public function test_watch_history_mark_as_completed(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();
        $history = WatchHistory::factory()->create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'completed' => false
        ]);

        $history->markAsCompleted();

        $this->assertTrue($history->fresh()->completed);
    }

    /**
     * Test watch history watch percentage attribute.
     */
    public function test_watch_history_watch_percentage(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create(['duration' => 100]);
        $history = WatchHistory::factory()->create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'progress' => 50
        ]);

        $this->assertEquals(50.0, $history->watch_percentage);
    }

    /**
     * Test watch history isRecent method.
     */
    public function test_watch_history_is_recent(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();

        $recentHistory = WatchHistory::factory()->create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'watched_at' => now()
        ]);
        $oldHistory = WatchHistory::factory()->create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'watched_at' => now()->subDays(2)
        ]);

        $this->assertTrue($recentHistory->isRecent(24));
        $this->assertFalse($oldHistory->isRecent(24));
    }

    /**
     * Test watch history fillable attributes.
     */
    public function test_watch_history_fillable_attributes(): void
    {
        $history = new WatchHistory();
        $fillable = $history->getFillable();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('video_id', $fillable);
        $this->assertContains('progress', $fillable);
        $this->assertContains('completed', $fillable);
        $this->assertContains('watched_at', $fillable);
    }

    /**
     * Test watch history default values.
     */
    public function test_watch_history_default_values(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();

        $history = WatchHistory::create([
            'user_id' => $user->id,
            'video_id' => $video->id,
        ]);

        $this->assertEquals(0, $history->progress);
        $this->assertFalse($history->completed);
    }
}

