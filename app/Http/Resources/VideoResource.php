<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * VideoResource
 *
 * Formats video data for API responses.
 * Includes computed URLs for streaming and file access.
 */
class VideoResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'file_path' => $this->file_path,
            'thumbnail_path' => $this->thumbnail_path,

            // Computed URLs for frontend convenience
            'video_url' => $this->video_url,
            'thumbnail_url' => $this->thumbnail_url,
            'video_file_url' => $this->video_file_url,

            // Video metadata
            'file_size' => $this->file_size,
            'duration' => $this->duration,
            'formatted_duration' => $this->formatted_duration,
            'views_count' => $this->views_count,
            'likes_count' => $this->likes_count ?? $this->when(isset($this->likes_count), $this->likes_count),
            'status' => $this->status,
            'visibility' => $this->visibility,
            'visibility_label' => $this->visibility_label,

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar' => $this->user->avatar,
                    'subscribers_count' => $this->when(isset($this->user->subscribers_count), $this->user->subscribers_count),
                ];
            }),

            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
        ];
    }
}

