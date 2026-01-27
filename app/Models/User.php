<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role',
        'is_active',
        'email_verified_at',
        // Channel profile fields
        'channel_name',
        'channel_description',
        'channel_banner',
        'stream_key',
        'stream_status',
        'stream_started_at',
        'stream_viewers',
        'stream_title',
        'stream_settings',
        'total_views',
        'total_subscribers',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the videos uploaded by this user.
     *
     * @return HasMany
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Get the playlists created by this user.
     *
     * @return HasMany
     */
    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }

    /**
     * Get the watch history for this user.
     *
     * @return HasMany
     */
    public function watchHistory(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    /**
     * Get the videos that this user has liked.
     *
     * @return BelongsToMany
     */
    public function likedVideos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'video_likes')
            ->withTimestamps()
            ->orderByDesc('video_likes.created_at');
    }

    /**
     * Get the channels this user is subscribed to (following).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function subscriptions(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subscriptions', 'subscriber_id', 'channel_id')
            ->withTimestamps()
            ->orderByDesc('subscriptions.created_at');
    }

    /**
     * Get the subscribers of this user (followers).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subscriptions', 'channel_id', 'subscriber_id')
            ->withTimestamps()
            ->orderByDesc('subscriptions.created_at');
    }

    /**
     * Check if this user is subscribed to a specific channel.
     *
     * @param int $channelId
     * @return bool
     */
    public function isSubscribedTo(int $channelId): bool
    {
        return $this->subscriptions()->where('channel_id', $channelId)->exists();
    }

    /**
     * Check if this user has a specific subscriber.
     *
     * @param int $subscriberId
     * @return bool
     */
    public function hasSubscriber(int $subscriberId): bool
    {
        return $this->subscribers()->where('subscriber_id', $subscriberId)->exists();
    }

    /**
     * Get the number of subscribers this user has.
     *
     * @return int
     */
    public function getSubscribersCountAttribute(): int
    {
        return $this->subscribers()->count();
    }

    /**
     * Get the number of channels this user is subscribed to.
     *
     * @return int
     */
    public function getSubscriptionsCountAttribute(): int
    {
        return $this->subscriptions()->count();
    }

    /**
     * Get avatar URL.
     *
     * @return string|null
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return null;
    }

    /**
     * Get channel banner URL.
     *
     * @return string|null
     */
    public function getChannelBannerUrlAttribute(): ?string
    {
        if ($this->channel_banner) {
            return asset('storage/' . $this->channel_banner);
        }
        return null;
    }

    /**
     * Get the actual subscriber count from database.
     *
     * @return int
     */
    public function getActualSubscribersCount(): int
    {
        return \Illuminate\Support\Facades\DB::table('subscriptions')
            ->where('channel_id', $this->id)
            ->count();
    }

    /**
     * Get the actual video views count from database.
     *
     * @return int
     */
    public function getActualViewsCount(): int
    {
        return $this->videos()->sum('views_count');
    }

    /**
     * Get the actual videos count.
     *
     * @return int
     */
    public function getActualVideosCount(): int
    {
        return $this->videos()->count();
    }

    /**
     * Check if user is an admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a creator.
     *
     * @return bool
     */
    public function isCreator(): bool
    {
        return $this->role === 'creator';
    }

    /**
     * Check if user is a regular viewer (user).
     *
     * @return bool
     */
    public function isViewer(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if user can upload videos.
     *
     * @return bool
     */
    public function canUploadVideos(): bool
    {
        // Admins cannot upload normally, creators and users can
        return in_array($this->role, ['creator', 'user']);
    }

    /**
     * Check if user can approve videos.
     *
     * @return bool
     */
    public function canApproveVideos(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can manage users.
     *
     * @return bool
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }
}
