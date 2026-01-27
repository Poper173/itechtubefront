<?php

namespace Tests\Unit\Models;

use App\Models\Playlist;
use App\Models\Video;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

class PlaylistModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a playlist can be created with required attributes.
     */
    public function test_playlist_can_be_created(): void
    {
        $user = User::factory()->create();

        $playlist = Playlist::create([
            'user_id' => $user->id,
            'name' => 'My Playlist',
            'description' => 'A test playlist',
            'is_public' => false,
        ]);

        $this->assertNotNull($playlist->id);
        $this->assertEquals('My Playlist', $playlist->name);
        $this->assertEquals($user->id, $playlist->user_id);
    }

    /**
     * Test playlist belongs to user relationship.
     */
    public function test_playlist_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $playlist->user);
        $this->assertEquals($user->id, $playlist->user->id);
    }

    /**
     * Test playlist has many videos relationship.
     */
    public function test_playlist_has_many_videos(): void
    {
        $playlist = Playlist::factory()->create();
        $video1 = Video::factory()->create();
        $video2 = Video::factory()->create();

        $playlist->videos()->attach([$video1->id, $video2->id], ['position' => 0]);

        $this->assertCount(2, $playlist->videos);
    }

    /**
     * Test playlist public scope.
     */
    public function test_playlist_public_scope(): void
    {
        $publicPlaylist = Playlist::factory()->create(['is_public' => true]);
        $privatePlaylist = Playlist::factory()->create(['is_public' => false]);

        $publicPlaylists = Playlist::public()->get();

        $this->assertCount(1, $publicPlaylists);
        $this->assertEquals($publicPlaylist->id, $publicPlaylists->first()->id);
    }

    /**
     * Test playlist private scope.
     */
    public function test_playlist_private_scope(): void
    {
        $publicPlaylist = Playlist::factory()->create(['is_public' => true]);
        $privatePlaylist = Playlist::factory()->create(['is_public' => false]);

        $privatePlaylists = Playlist::private()->get();

        $this->assertCount(1, $privatePlaylists);
        $this->assertEquals($privatePlaylist->id, $privatePlaylists->first()->id);
    }

    /**
     * Test playlist isPublic method.
     */
    public function test_playlist_is_public_method(): void
    {
        $publicPlaylist = Playlist::factory()->create(['is_public' => true]);
        $privatePlaylist = Playlist::factory()->create(['is_public' => false]);

        $this->assertTrue($publicPlaylist->isPublic());
        $this->assertFalse($privatePlaylist->isPublic());
    }

    /**
     * Test playlist fillable attributes.
     */
    public function test_playlist_fillable_attributes(): void
    {
        $playlist = new Playlist();
        $fillable = $playlist->getFillable();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('is_public', $fillable);
    }

    /**
     * Test playlist casts.
     */
    public function test_playlist_casts(): void
    {
        $playlist = new Playlist();
        $casts = $playlist->getCasts();

        $this->assertArrayHasKey('is_public', $casts);
        $this->assertEquals('boolean', $casts['is_public']);
    }

    /**
     * Test playlist of user scope.
     */
    public function test_playlist_of_user_scope(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $playlist1 = Playlist::factory()->create(['user_id' => $user1->id]);
        $playlist2 = Playlist::factory()->create(['user_id' => $user2->id]);

        $user1Playlists = Playlist::ofUser($user1->id)->get();

        $this->assertCount(1, $user1Playlists);
        $this->assertEquals($playlist1->id, $user1Playlists->first()->id);
    }

    /**
     * Test playlist total duration attribute.
     */
    public function test_playlist_total_duration_attribute(): void
    {
        $playlist = Playlist::factory()->create();
        $video1 = Video::factory()->create(['duration' => 120]);
        $video2 = Video::factory()->create(['duration' => 180]);

        $playlist->videos()->attach([$video1->id, $video2->id], ['position' => 0]);

        $this->assertEquals(300, $playlist->total_duration);
    }
}

