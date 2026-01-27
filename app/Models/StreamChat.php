<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StreamChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'user_id',
        'message',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Get the user who sent the message
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the channel the message was sent to
     */
    public function channel()
    {
        return $this->belongsTo(User::class, 'channel_id');
    }

    /**
     * Get messages for a channel, ordered by creation
     */
    public static function getMessagesForChannel($channelId, $limit = 50, $sinceId = null)
    {
        $query = static::where('channel_id', $channelId)
            ->where('is_system', false)
            ->with('user:id,name,avatar')
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($sinceId) {
            $query->where('id', '>', $sinceId);
        }

        return $query->get()->reverse()->values();
    }

    /**
     * Clean up old messages for a channel
     */
    public static function cleanOldMessages($channelId, $keepLast = 100)
    {
        $count = static::where('channel_id', $channelId)->count();

        if ($count > $keepLast) {
            $idsToKeep = static::where('channel_id', $channelId)
                ->orderBy('created_at', 'desc')
                ->limit($keepLast)
                ->pluck('id');

            static::where('channel_id', $channelId)
                ->whereNotIn('id', $idsToKeep)
                ->delete();
        }
    }
}

