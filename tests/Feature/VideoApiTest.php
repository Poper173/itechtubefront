<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can list all videos (public endpoint).
     */
    public function test_can_list_all_videos(): void
    {
        Video::factory()->count(3)->create();

        $response = $this->getJson('/api/videos');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'file_size',
                            'duration',
                            'views_count',
                            'status',
                            'created_at',
                            'updated_at',
                            'user' => ['id', 'name', 'avatar'],
                            'category' => ['id', 'name', 'slug'],
                        ],
                    ],
                    'links',
                    'meta',
                ],
            ])
            ->assertJsonCount(3, 'data.data');
    }

    /**
     * Test can filter videos by category.
     */
    public function test_can_filter_videos_by_category(): void
    {
        $category = Category::factory()->create();
        Video::factory()->count(2)->create(['category_id' => $category->id]);
        Video::factory()->count(1)->create(); // Video without category

        $response = $this->getJson('/api/videos?category_id=' . $category->id);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.data');
    }

    /**
     * Test can sort videos by views.
     */
    public function test_can_sort_videos_by_views(): void
    {
        Video::factory()->create(['views_count' => 100]);
        Video::factory()->create(['views_count' => 500]);
        Video::factory()->create(['views_count' => 200]);

        $response = $this->getJson('/api/videos?sort_by=views_count&sort_order=desc');

        $response->assertStatus(200);

        $data = $response['data']['data'];
        $this->assertEquals(500, $data[0]['views_count']);
        $this->assertEquals(200, $data[1]['views_count']);
        $this->assertEquals(100, $data[2]['views_count']);
    }

    /**
     * Test can show single video (public endpoint).
     */
    public function test_can_show_single_video(): void
    {
        $video = Video::factory()->create([
            'title' => 'Test Video',
            'views_count' => 100,
        ]);

        $response = $this->getJson('/api/videos/' . $video->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Video retrieved successfully',
                'data' => [
                    'id' => $video->id,
                    'title' => 'Test Video',
                ],
            ]);

        // Verify view count was incremented
        $this->assertEquals(101, $video->fresh()->views_count);
    }

    /**
     * Test returns 404 for non-existent video.
     */
    public function test_returns_404_for_non_existent_video(): void
    {
        $response = $this->getJson('/api/videos/999');

        $response->assertStatus(404);
    }

    /**
     * Test can search videos.
     */
    public function test_can_search_videos(): void
    {
        Video::factory()->create(['title' => 'Laravel Tutorial', 'description' => 'Learn Laravel']);
        Video::factory()->create(['title' => 'React Tutorial', 'description' => 'Learn React']);
        Video::factory()->create(['title' => 'Vue Tutorial', 'description' => 'Learn Vue']);

        $response = $this->getJson('/api/videos/search?q=Laravel');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
                'search_term',
            ])
            ->assertJsonCount(1, 'data.data')
            ->assertEquals('Laravel', $response['search_term']);
    }

    /**
     * Test authenticated user can upload video.
     */
    public function test_authenticated_user_can_upload_video(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $videoFile = UploadedFile::fake()->create('test-video.mp4', 1000, 'video/mp4');
        $thumbnailFile = UploadedFile::fake()->create('thumbnail.jpg', 100, 'image/jpeg');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/videos', [
            'title' => 'My New Video',
            'description' => 'Video description',
            'video' => $videoFile,
            'thumbnail' => $thumbnailFile,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'file_path',
                    'thumbnail_path',
                    'file_size',
                    'status',
                    'user',
                    'category',
                ],
            ])
            ->assertJson([
                'message' => 'Video uploaded successfully',
                'data' => [
                    'title' => 'My New Video',
                    'status' => 'active',
                ],
            ]);

        // Verify files were stored
        Storage::disk('public')->assertExists('videos/');
        Storage::disk('public')->assertExists('thumbnails/');
    }

    /**
     * Test video upload validates required fields.
     */
    public function test_video_upload_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/videos', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'video']);
    }

    /**
     * Test video upload validates file type.
     */
    public function test_video_upload_validates_file_type(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $invalidFile = UploadedFile::fake()->create('test.txt', 1000, 'text/plain');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/videos', [
            'title' => 'My Video',
            'video' => $invalidFile,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['video']);
    }

    /**
     * Test unauthenticated user cannot upload video.
     */
    public function test_unauthenticated_user_cannot_upload_video(): void
    {
        Storage::fake('public');

        $videoFile = UploadedFile::fake()->create('test-video.mp4', 1000, 'video/mp4');

        $response = $this->postJson('/api/videos', [
            'title' => 'My Video',
            'video' => $videoFile,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can update own video.
     */
    public function test_authenticated_user_can_update_own_video(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $video = Video::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/videos/' . $video->id, [
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Video updated successfully',
                'data' => [
                    'title' => 'Updated Title',
                    'description' => 'Updated description',
                ],
            ]);
    }

    /**
     * Test user cannot update another user's video.
     */
    public function test_user_cannot_update_another_users_video(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token = $user2->createToken('test-token')->plainTextToken;
        $video = Video::factory()->create(['user_id' => $user1->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/videos/' . $video->id, [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized. You can only update your own videos.',
            ]);
    }

    /**
     * Test authenticated user can delete own video.
     */
    public function test_authenticated_user_can_delete_own_video(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $video = Video::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/videos/' . $video->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Video deleted successfully',
            ]);

        $this->assertDatabaseMissing('videos', [
            'id' => $video->id,
        ]);
    }

    /**
     * Test user cannot delete another user's video.
     */
    public function test_user_cannot_delete_another_users_video(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token = $user2->createToken('test-token')->plainTextToken;
        $video = Video::factory()->create(['user_id' => $user1->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/videos/' . $video->id);

        $response->assertStatus(403);
    }

    /**
     * Test authenticated user can get own videos.
     */
    public function test_authenticated_user_can_get_own_videos(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        Video::factory()->count(3)->create(['user_id' => $user->id]);
        Video::factory()->count(2)->create(); // Other users' videos

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-videos');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data');
    }
}

