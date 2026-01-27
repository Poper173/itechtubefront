<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Video>
 */
class VideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'user_id' => \App\Models\User::factory(),
            'category_id' => \App\Models\Category::factory(),
            'file_path' => 'videos/' . fake()->uuid() . '.mp4',
            'thumbnail_path' => 'thumbnails/' . fake()->uuid() . '.jpg',
            'file_size' => fake()->numberBetween(1000000, 500000000), // 1MB to 500MB
            'duration' => fake()->numberBetween(60, 3600), // 1 minute to 1 hour
            'views_count' => fake()->numberBetween(0, 100000),
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the video is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the video is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Indicate that the video has no category.
     */
    public function withoutCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => null,
        ]);
    }

    /**
     * Indicate that the video has no description.
     */
    public function withoutDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => null,
        ]);
    }

    /**
     * Indicate that the video has many views.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'views_count' => fake()->numberBetween(100000, 10000000),
        ]);
    }

    /**
     * Indicate that the video has no thumbnail.
     */
    public function withoutThumbnail(): static
    {
        return $this->state(fn (array $attributes) => [
            'thumbnail_path' => null,
        ]);
    }
}

