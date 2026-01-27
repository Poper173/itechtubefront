<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WatchHistory>
 */
class WatchHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'video_id' => \App\Models\Video::factory(),
            'progress' => fake()->numberBetween(0, 3600),
            'completed' => fake()->boolean(30),
            'watched_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the watch history is incomplete.
     */
    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed' => false,
        ]);
    }

    /**
     * Indicate that the watch history is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed' => true,
            'progress' => 3600, // Full duration
        ]);
    }

    /**
     * Indicate that the watch history is recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'watched_at' => now(),
        ]);
    }

    /**
     * Indicate that the watch history is old.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'watched_at' => fake()->dateTimeBetween('-1 year', '-1 month'),
        ]);
    }
}

