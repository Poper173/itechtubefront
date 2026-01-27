<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * PlaylistVideo Model
 *
 * Represents the pivot table entry for a video in a playlist.
 * Tracks which videos are in which playlists and their position.
 *
 * @property int $id
 * @property int $playlist_id
 * @property int $video_id
 * @property int $position
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PlaylistVideo extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'playlist_id',
        'video_id',
        'position',
    ];

    /**
     * Cast attributes to specific types.
     */
    protected $casts = [
        'position' => 'integer',
    ];

    /**
     * Get the playlist this entry belongs to.
     *
     * @return BelongsTo
     */
    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    /**
     * Get the video associated with this entry.
     *
     * @return BelongsTo
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Get the playlist this video belongs to.
     *
     * @return BelongsToMany
     */
    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class, 'playlist_videos')
            ->withPivot('id', 'position')
            ->orderBy('position');
    }
}

