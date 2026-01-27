<?php

namespace Tests\Unit\Models;

use App\Models\Video;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

class VideoModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a video can be created with required attributes.
     */
    public function test_video_can_be_created(): void
    {
        $user = User::factory()->create();

        $video = Video::create([
            'title' => 'Test Video',
            'description' => 'A test video description',
            'user_id' => $user->id,
            'file_path' => 'videos/test-video.mp4',
            'file_size' => 1024000,
            'duration' => 120,
            'views_count' => 0,
            'status' => 'active',
        ]);

        $this->assertNotNull($video->id);
        $this->assertEquals('Test Video', $video->title);
        $this->assertEquals($user->id, $video->user_id);
    }

    /**
     * Test video belongs to user relationship.
     */
    public function test_video_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $video->user);
        $this->assertEquals($user->id, $video->user->id);
    }

    /**
     * Test video belongs to category relationship.
     */
    public function test_video_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $video = Video::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $video->category);
        $this->assertEquals($category->id, $video->category->id);
    }

    /**
     * Test video can have null category.
     */
    public function test_video_can_have_null_category(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create(['user_id' => $user->id, 'category_id' => null]);

        $this->assertNull($video->category);
    }

    /**
     * Test video increment views method.
     */
    public function test_video_increment_views(): void
    {
        $video = Video::factory()->create(['views_count' => 100]);
        $this->assertEquals(100, $video->views_count);

        $video->incrementViews();
        $video->refresh();

        $this->assertEquals(101, $video->views_count);
    }

    /**
     * Test video formatted size accessor.
     */
    public function test_video_formatted_size_attribute(): void
    {
        $video = Video::factory()->create(['file_size' => 1024]); // 1KB
        $this->assertEquals('1.00 KB', $video->formatted_size);

        $video2 = Video::factory()->create(['file_size' => 1048576]); // 1MB
        $this->assertEquals('1.00 MB', $video2->formatted_size);

        $video3 = Video::factory()->create(['file_size' => 1073741824]); // 1GB
        $this->assertEquals('1.00 GB', $video3->formatted_size);
    }

    /**
     * Test video formatted duration accessor.
     */
    public function test_video_formatted_duration_attribute(): void
    {
        $video = Video::factory()->create(['duration' => 65]); // 1:05
        $this->assertEquals('01:05', $video->formatted_duration);

        $video2 = Video::factory()->create(['duration' => 3661]); // 1:01:01
        $this->assertEquals('01:01:01', $video2->formatted_duration);
    }

    /**
     * Test video is streamable when active.
     */
    public function test_video_is_streamable_when_active(): void
    {
        $video = Video::factory()->create(['status' => 'active']);
        $this->assertTrue($video->isStreamable());

        $inactiveVideo = Video::factory()->create(['status' => 'inactive']);
        $this->assertFalse($inactiveVideo->isStreamable());

        $processingVideo = Video::factory()->create(['status' => 'processing']);
        $this->assertFalse($processingVideo->isStreamable());
    }

    /**
     * Test video active scope.
     */
    public function test_video_active_scope(): void
    {
        $activeVideo = Video::factory()->create(['status' => 'active']);
        $inactiveVideo = Video::factory()->create(['status' => 'inactive']);

        $activeVideos = Video::active()->get();

        $this->assertCount(1, $activeVideos);
        $this->assertEquals($activeVideo->id, $activeVideos->first()->id);
    }

    /**
     * Test video search scope.
     */
    public function test_video_search_scope(): void
    {
        $video1 = Video::factory()->create(['title' => 'Laravel Tutorial', 'description' => 'Learn Laravel']);
        $video2 = Video::factory()->create(['title' => 'React Tutorial', 'description' => 'Learn React']);

        $results = Video::search('Laravel')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($video1->id, $results->first()->id);
    }

    /**
     * Test video most viewed scope.
     */
    public function test_video_most_viewed_scope(): void
    {
        $video1 = Video::factory()->create(['views_count' => 100]);
        $video2 = Video::factory()->create(['views_count' => 500]);
        $video3 = Video::factory()->create(['views_count' => 200]);

        $mostViewed = Video::mostViewed()->get();

        $this->assertEquals(500, $mostViewed->first()->views_count);
        $this->assertEquals(100, $mostViewed->last()->views_count);
    }

    /**
     * Test video newest scope.
     */
    public function test_video_newest_scope(): void
    {
        $video1 = Video::factory()->create();
        $video2 = Video::factory()->create();

        $newest = Video::newest()->first();

        $this->assertEquals($video2->id, $newest->id);
    }

    /**
     * Test video fillable attributes.
     */
    public function test_video_fillable_attributes(): void
    {
        $video = new Video();
        $fillable = $video->getFillable();

        $this->assertContains('title', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('user_id', $fillable);
        $this->assertContains('category_id', $fillable);
        $this->assertContains('file_path', $fillable);
        $this->assertContains('file_size', $fillable);
        $this->assertContains('duration', $fillable);
        $this->assertContains('views_count', $fillable);
        $this->assertContains('status', $fillable);
    }
}

