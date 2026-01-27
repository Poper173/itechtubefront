<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CommentResource
 *
 * Formats comment data for API responses.
 */
class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
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
            'likes_count' => $this->likes_count ?? 0,
            'dislikes_count' => $this->dislikes_count ?? 0,
            'replies_count' => $this->replies_count ?? 0,

            // Include like status if user is authenticated
            'is_liked' => $request->user() ? $this->isLikedBy($request->user()->id) : false,

            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar' => $this->user->avatar,
                ];
            }),

            // Replies (only if loaded)
            'replies' => $this->whenLoaded('replies', function () {
                return CommentResource::collection($this->replies);
            }),
        ];
    }
}

