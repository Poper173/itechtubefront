<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Models\Playlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaylistApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test authenticated user can list own playlists.
     */
    public function test_authenticated_user_can_list_own_playlists(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        Playlist::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/playlists');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'is_public',
                            'videos_count',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data.data');
    }

    /**
     * Test authenticated user can create playlist.
     */
    public function test_authenticated_user_can_create_playlist(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/playlists', [
            'name' => 'My Playlist',
            'description' => 'A test playlist',
            'is_public' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'is_public',
                    'user',
                    'videos',
                ],
            ])
            ->assertJson([
                'message' => 'Playlist created successfully',
                'data' => [
                    'name' => 'My Playlist',
                    'description' => 'A test playlist',
                    'is_public' => true,
                ],
            ]);

        $this->assertDatabaseHas('playlists', [
            'name' => 'My Playlist',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test playlist creation validates required name.
     */
    public function test_playlist_creation_validates_name_required(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/playlists', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test authenticated user can view own playlist.
     */
    public function test_authenticated_user_can_view_own_playlist(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $playlist = Playlist::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/playlists/' . $playlist->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Playlist retrieved successfully',
                'data' => [
                    'id' => $playlist->id,
                    'name' => $playlist->name,
                ],
            ]);
    }

    /**
     * Test user cannot view another user's private playlist.
     */
    public function test_user_cannot_view_another_users_private_playlist(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token = $user2->createToken('test-token')->plainTextToken;

        $privatePlaylist = Playlist::factory()->create([
            'user_id' => $user1->id,
            'is_public' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/playlists/' . $privatePlaylist->id);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized to view this playlist',
            ]);
    }

    /**
     * Test authenticated user can update own playlist.
     */
    public function test_authenticated_user_can_update_own_playlist(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $playlist = Playlist::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/playlists/' . $playlist->id, [
            'name' => 'Updated Playlist Name',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Playlist updated successfully',
                'data' => [
                    'name' => 'Updated Playlist Name',
                ],
            ]);
    }

    /**
     * Test user cannot update another user's playlist.
     */
    public function test_user_cannot_update_another_users_playlist(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token = $user2->createToken('test-token')->plainTextToken;
        $playlist = Playlist::factory()->create(['user_id' => $user1->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/playlists/' . $playlist->id, [
            'name' => 'Hacked Playlist',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test authenticated user can delete own playlist.
     */
    public function test_authenticated_user_can_delete_own_playlist(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $playlist = Playlist::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/playlists/' . $playlist->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Playlist deleted successfully',
            ]);

        $this->assertDatabaseMissing('playlists', [
            'id' => $playlist->id,
        ]);
    }

    /**
     * Test authenticated user can add video to playlist.
     */
    public function test_authenticated_user_can_add_video_to_playlist(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $playlist = Playlist::factory()->create(['user_id' => $user->id]);
        $video = Video::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/playlists/' . $playlist->id . '/videos', [
            'video_id' => $video->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Video added to playlist successfully',
            ]);

        $this->assertDatabaseHas('playlist_videos', [
            'playlist_id' => $playlist->id,
            'video_id' => $video->id,
        ]);
    }

    /**
     * Test cannot add same video twice to playlist.
     */
    public function test_cannot_add_same_video_twice_to_playlist(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $playlist = Playlist::factory()->create(['user_id' => $user->id]);
        $video = Video::factory()->create();

        // Add video first time
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/playlists/' . $playlist->id . '/videos', [
            'video_id' => $video->id,
        ]);

        // Try to add same video again
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/playlists/' . $playlist->id . '/videos', [
            'video_id' => $video->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Video already exists in this playlist',
            ]);
    }

    /**
     * Test authenticated user can remove video from playlist.
     */
    public function test_authenticated_user_can_remove_video_from_playlist(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $playlist = Playlist::factory()->create(['user_id' => $user->id]);
        $video = Video::factory()->create();

        $playlist->videos()->attach($video->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/playlists/' . $playlist->id . '/videos/' . $video->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Video removed from playlist successfully',
            ]);

        $this->assertDatabaseMissing('playlist_videos', [
            'playlist_id' => $playlist->id,
            'video_id' => $video->id,
        ]);
    }

    /**
     * Test authenticated user can reorder videos in playlist.
     */
    public function test_authenticated_user_can_reorder_videos_in_playlist(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $playlist = Playlist::factory()->create(['user_id' => $user->id]);

        $video1 = Video::factory()->create();
        $video2 = Video::factory()->create();
        $video3 = Video::factory()->create();

        $playlist->videos()->attach([
            $video1->id => ['position' => 0],
            $video2->id => ['position' => 1],
            $video3->id => ['position' => 2],
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/playlists/' . $playlist->id . '/reorder', [
            'video_ids' => [$video3->id, $video1->id, $video2->id],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Videos reordered successfully',
            ]);

        // Verify new order
        $videos = $playlist->fresh()->videos->pluck('id')->toArray();
        $this->assertEquals([$video3->id, $video1->id, $video2->id], $videos);
    }

    /**
     * Test unauthenticated user cannot access playlist endpoints.
     */
    public function test_unauthenticated_user_cannot_access_playlists(): void
    {
        $response = $this->getJson('/api/playlists');
        $response->assertStatus(401);
    }
}

