<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subscriber_id',
        'channel_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who is subscribing (follower).
     *
     * @return BelongsTo
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subscriber_id');
    }

    /**
     * Get the channel being subscribed to.
     *
     * @return BelongsTo
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'channel_id');
    }

    /**
     * Toggle subscription status.
     *
     * @param int $subscriberId
     * @param int $channelId
     * @return array{subscribed: bool, subscribers_count: int}
     */
    public static function toggleSubscription(int $subscriberId, int $channelId): array
    {
        // Prevent subscribing to yourself
        if ($subscriberId === $channelId) {
            return [
                'subscribed' => false,
                'subscribers_count' => self::where('channel_id', $channelId)->count(),
                'error' => 'You cannot subscribe to yourself',
            ];
        }

        $existing = self::where('subscriber_id', $subscriberId)
            ->where('channel_id', $channelId)
            ->first();

        if ($existing) {
            // Unsubscribe
            $existing->delete();
            return [
                'subscribed' => false,
                'subscribers_count' => self::where('channel_id', $channelId)->count(),
            ];
        }

        // Subscribe
        self::create([
            'subscriber_id' => $subscriberId,
            'channel_id' => $channelId,
        ]);

        return [
            'subscribed' => true,
            'subscribers_count' => self::where('channel_id', $channelId)->count(),
        ];
    }

    /**
     * Check if a user is subscribed to a channel.
     *
     * @param int $subscriberId
     * @param int $channelId
     * @return bool
     */
    public static function isSubscribed(int $subscriberId, int $channelId): bool
    {
        return self::where('subscriber_id', $subscriberId)
            ->where('channel_id', $channelId)
            ->exists();
    }

    /**
     * Get subscriber count for a channel.
     *
     * @param int $channelId
     * @return int
     */
    public static function getSubscribersCount(int $channelId): int
    {
        return self::where('channel_id', $channelId)->count();
    }

    /**
     * Get subscription count (how many channels user is subscribed to).
     *
     * @param int $userId
     * @return int
     */
    public static function getSubscriptionsCount(int $userId): int
    {
        return self::where('subscriber_id', $userId)->count();
    }

    /**
     * Get all channel IDs a user is subscribed to.
     *
     * @param int $userId
     * @return array
     */
    public static function getSubscribedChannelIds(int $userId): array
    {
        return self::where('subscriber_id', $userId)
            ->pluck('channel_id')
            ->toArray();
    }
}

