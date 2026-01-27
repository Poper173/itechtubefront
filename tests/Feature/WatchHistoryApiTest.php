<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Models\WatchHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatchHistoryApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test authenticated user can get watch history.
     */
    public function test_authenticated_user_can_get_watch_history(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $video = Video::factory()->create();
        WatchHistory::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'video_id',
                            'progress',
                            'completed',
                            'watched_at',
                            'created_at',
                            'updated_at',
                            'video',
                        ],
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data.data');
    }

    /**
     * Test authenticated user can record watch progress.
     */
    public function test_authenticated_user_can_record_watch_progress(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $video = Video::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/history', [
            'video_id' => $video->id,
            'progress' => 60,
            'completed' => false,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'video_id',
                    'progress',
                    'completed',
                    'watched_at',
                ],
            ])
            ->assertJson([
                'message' => 'Watch progress recorded successfully',
                'data' => [
                    'video_id' => $video->id,
                    'progress' => 60,
                    'completed' => false,
                ],
            ]);

        $this->assertDatabaseHas('watch_histories', [
            'user_id' => $user->id,
            'video_id' => $video->id,
            'progress' => 60,
            'completed' => false,
        ]);
    }

    /**
     * Test watch history validates required video_id.
     */
    public function test_watch_history_validates_video_id_required(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/history', [
            'progress' => 60,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['video_id']);
    }

    /**
     * Test watch progress updates existing record.
     */
    public function test_watch_progress_updates_existing_record(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $video = Video::factory()->create();

        WatchHistory::factory()->create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'progress' => 30,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/history', [
            'video_id' => $video->id,
            'progress' => 60,
            'completed' => true,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('watch_histories', [
            'user_id' => $user->id,
            'video_id' => $video->id,
            'progress' => 60,
            'completed' => true,
        ]);
    }

    /**
     * Test authenticated user can get specific video watch progress.
     */
    public function test_authenticated_user_can_get_specific_video_progress(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $video = Video::factory()->create();

        WatchHistory::factory()->create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'progress' => 50,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/history/video/' . $video->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Watch history retrieved successfully',
                'data' => [
                    'video_id' => $video->id,
                    'progress' => 50,
                ],
            ]);
    }

    /**
     * Test returns null when no watch history for video.
     */
    public function test_returns_null_when_no_watch_history_for_video(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $video = Video::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/history/video/' . $video->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'No watch history found for this video',
                'data' => null,
            ]);
    }

    /**
     * Test authenticated user can update watch progress.
     */
    public function test_authenticated_user_can_update_watch_progress(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $video = Video::factory()->create();

        $history = WatchHistory::factory()->create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'progress' => 30,
            'completed' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/history/video/' . $video->id, [
            'progress' => 60,
            'completed' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Watch progress updated successfully',
            ]);

        $this->assertEquals(60, $history->fresh()->progress);
        $this->assertTrue($history->fresh()->completed);
    }

    /**
     * Test authenticated user can delete watch history entry.
     */
    public function test_authenticated_user_can_delete_watch_history_entry(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $history = WatchHistory::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/history/' . $history->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Watch history entry deleted successfully',
            ]);

        $this->assertDatabaseMissing('watch_histories', [
            'id' => $history->id,
        ]);
    }

    /**
     * Test authenticated user can clear all watch history.
     */
    public function test_authenticated_user_can_clear_all_watch_history(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        WatchHistory::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/history');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'All watch history cleared successfully',
            ]);

        $this->assertDatabaseMissing('watch_histories', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test authenticated user can get continue watching list.
     */
    public function test_authenticated_user_can_get_continue_watching(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Create incomplete watch history
        WatchHistory::factory()->create([
            'user_id' => $user->id,
            'completed' => false,
            'watched_at' => now()->subHour(),
        ]);
        WatchHistory::factory()->create([
            'user_id' => $user->id,
            'completed' => false,
            'watched_at' => now()->subDay(),
        ]);

        // Create completed watch history (should not appear)
        WatchHistory::factory()->create([
            'user_id' => $user->id,
            'completed' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/history/continue-watching');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Continue watching list retrieved successfully',
            ]);

        // Should only have incomplete entries
        $this->assertCount(2, $response['data']);
    }

    /**
     * Test can filter history by incomplete.
     */
    public function test_can_filter_history_by_incomplete(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        WatchHistory::factory()->create([
            'user_id' => $user->id,
            'completed' => false,
        ]);
        WatchHistory::factory()->create([
            'user_id' => $user->id,
            'completed' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/history?incomplete=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');

        $this->assertFalse($response['data']['data'][0]['completed']);
    }

    /**
     * Test can filter history by completed.
     */
    public function test_can_filter_history_by_completed(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        WatchHistory::factory()->create([
            'user_id' => $user->id,
            'completed' => false,
        ]);
        WatchHistory::factory()->create([
            'user_id' => $user->id,
            'completed' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/history?completed=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');

        $this->assertTrue($response['data']['data'][0]['completed']);
    }

    /**
     * Test unauthenticated user cannot access watch history.
     */
    public function test_unauthenticated_user_cannot_access_watch_history(): void
    {
        $response = $this->getJson('/api/history');
        $response->assertStatus(401);
    }

    /**
     * Test user cannot delete another user's watch history.
     */
    public function test_user_cannot_delete_another_users_watch_history(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token = $user2->createToken('test-token')->plainTextToken;

        $history = WatchHistory::factory()->create([
            'user_id' => $user1->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/history/' . $history->id);

        $response->assertStatus(404);
    }
}

