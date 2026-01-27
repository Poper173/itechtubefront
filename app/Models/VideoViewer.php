<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VideoViewer Model
 *
 * Tracks unique video viewers by IP address and/or user_id.
 * Used to prevent duplicate view counting when users watch videos
 * as guests and then logged in users.
 *
 * @property int $id
 * @property int $video_id
 * @property int|null $user_id
 * @property string $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $viewed_at
 */
class VideoViewer extends Model
{
    use HasFactory;

    /**
     * Disable automatic timestamp management.
     * The table only has 'viewed_at' column, not 'created_at' and 'updated_at'.
     */
    public $timestamps = false;

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'video_id',
        'user_id',
        'ip_address',
        'user_agent',
        'viewed_at',
    ];

    /**
     * Cast attributes to specific types.
     */
    protected $casts = [
        'video_id' => 'integer',
        'user_id' => 'integer',
        'viewed_at' => 'datetime',
    ];

    /**
     * The video that was viewed.
     *
     * @return BelongsTo
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * The user who viewed (if authenticated).
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a video has been viewed by a specific viewer (user or IP).
     *
     * @param int $videoId
     * @param int|null $userId
     * @param string $ipAddress
     * @return bool
     */
    public static function hasViewed(int $videoId, ?int $userId, string $ipAddress): bool
    {
        // Check by user_id first (more specific)
        if ($userId) {
            $byUser = static::where('video_id', $videoId)
                ->where('user_id', $userId)
                ->exists();
            if ($byUser) {
                return true;
            }
        }

        // Check by IP address (for guests or before login)
        return static::where('video_id', $videoId)
            ->where('ip_address', $ipAddress)
            ->exists();
    }

    /**
     * Record a video view.
     *
     * @param int $videoId
     * @param int|null $userId
     * @param string $ipAddress
     * @param string|null $userAgent
     * @return bool True if view was recorded (first time), false if already viewed
     */
    public static function recordView(int $videoId, ?int $userId, string $ipAddress, ?string $userAgent = null): bool
    {
        // Check if already viewed
        if (static::hasViewed($videoId, $userId, $ipAddress)) {
            return false;
        }

        // If user is logged in, update any existing IP-based record to link to user
        if ($userId) {
            $existingIpRecord = static::where('video_id', $videoId)
                ->where('ip_address', $ipAddress)
                ->whereNull('user_id')
                ->first();

            if ($existingIpRecord) {
                // Update existing guest record to link to user
                $existingIpRecord->update([
                    'user_id' => $userId,
                    'viewed_at' => now(),
                ]);
                return false; // Already existed as guest, don't increment
            }
        }

        // Create new view record
        static::create([
            'video_id' => $videoId,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'viewed_at' => now(),
        ]);

        return true; // New view recorded
    }

    /**
     * Get viewer count for a video.
     *
     * @param int $videoId
     * @return int
     */
    public static function getViewCount(int $videoId): int
    {
        return static::where('video_id', $videoId)->count();
    }

    /**
     * Get client IP address from request.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public static function getClientIp($request): string
    {
        // Check for forwarded IP (behind proxy)
        $ips = $request->getClientIps();
        if (!empty($ips)) {
            return $ips[0];
        }

        return $request->ip() ?? '0.0.0.0';
    }
}

