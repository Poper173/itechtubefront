<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Comment Model
 *
 * Represents a comment on a video.
 * Supports nested replies and likes.
 *
 * @property int $id
 * @property string $content
 * @property int $user_id
 * @property int $video_id
 * @property int|null $parent_id
 * @property bool $is_approved
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Comment extends Model
{
    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'content',
        'user_id',
        'video_id',
        'parent_id',
        'is_approved',
    ];

    /**
     * Cast attributes to specific types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
        ];
    }

    /**
     * Get the user who wrote the comment.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the video that was commented on.
     *
     * @return BelongsTo
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Get the parent comment (for replies).
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get replies to this comment.
     *
     * @return HasMany
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * Get likes for this comment.
     *
     * @return HasMany
     */
    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }

    /**
     * Get like count attribute.
     *
     * @return int
     */
    public function getLikesCountAttribute(): int
    {
        return $this->likes()->where('is_like', true)->count();
    }

    /**
     * Get dislike count attribute.
     *
     * @return int
     */
    public function getDislikesCountAttribute(): int
    {
        return $this->likes()->where('is_like', false)->count();
    }

    /**
     * Check if the comment is a reply.
     *
     * @return bool
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Check if the comment is liked by a specific user.
     *
     * @param int $userId
     * @return bool
     */
    public function isLikedBy(int $userId): bool
    {
        return $this->likes()
            ->where('user_id', $userId)
            ->where('is_like', true)
            ->exists();
    }

    /**
     * Get the reply count.
     *
     * @return int
     */
    public function getRepliesCountAttribute(): int
    {
        return $this->replies()->count();
    }

    /**
     * Scope a query to only include approved comments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\EloquentBuilder
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope a query to only include top-level comments (no parent).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\EloquentBuilder
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to order by newest first.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\EloquentBuilder
     */
    public function scopeNewest($query)
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Scope a query to order by most likes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\EloquentBuilder
     */
    public function scopeMostLiked($query)
    {
        return $query->withCount('likes as likes_count')
            ->orderByDesc('likes_count');
    }

    /**
     * Get the comment as a resource array for API responses.
     *
     * @return array
     */
    public function toResourceArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'user_id' => $this->user_id,
            'video_id' => $this->video_id,
            'parent_id' => $this->parent_id,
            'is_approved' => $this->is_approved,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'likes_count' => $this->likes_count,
            'dislikes_count' => $this->dislikes_count,
            'replies_count' => $this->replies_count,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
            ] : null,
        ];
    }
}

