<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Video;
use App\Models\Category;
use App\Models\Playlist;
use App\Models\WatchHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a user can be created with required attributes.
     */
    public function test_user_can_be_created(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    /**
     * Test user has many videos relationship.
     */
    public function test_user_has_many_videos(): void
    {
        $user = User::factory()->create();
        $video1 = Video::factory()->create(['user_id' => $user->id]);
        $video2 = Video::factory()->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->videos);
        $this->assertTrue($user->videos->contains($video1));
        $this->assertTrue($user->videos->contains($video2));
    }

    /**
     * Test user has many playlists relationship.
     */
    public function test_user_has_many_playlists(): void
    {
        $user = User::factory()->create();
        $playlist1 = Playlist::factory()->create(['user_id' => $user->id]);
        $playlist2 = Playlist::factory()->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->playlists);
        $this->assertTrue($user->playlists->contains($playlist1));
        $this->assertTrue($user->playlists->contains($playlist2));
    }

    /**
     * Test user has many watch history relationship.
     */
    public function test_user_has_many_watch_history(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();

        WatchHistory::factory()->create(['user_id' => $user->id, 'video_id' => $video->id]);
        WatchHistory::factory()->create(['user_id' => $user->id, 'video_id' => $video->id]);

        $this->assertCount(2, $user->watchHistory);
    }

    /**
     * Test user password is hashed automatically.
     */
    public function test_user_password_is_hashed(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'plainpassword',
        ]);

        $this->assertNotEquals('plainpassword', $user->password);
        $this->assertTrue(password_verify('plainpassword', $user->password));
    }

    /**
     * Test user email must be unique.
     */
    public function test_user_email_must_be_unique(): void
    {
        User::create([
            'name' => 'User 1',
            'email' => 'unique@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([
            'name' => 'User 2',
            'email' => 'unique@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}

