<?php

namespace Database\Factories;

use App\Models\Guild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guild>
 */
class GuildFactory extends Factory
{
    protected $model = Guild::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'region' => fake()->randomElement(['us', 'eu']),
            'realm' => fake()->slug(2),
            'name' => fake()->slug(3),
            'is_classic' => false,
            'search_count' => fake()->numberBetween(0, 100),
            'last_searched_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'data' => null,
            'data_fetched_at' => null,
            'fetch_status' => 'idle',
            'fetch_queued_at' => null,
        ];
    }

    /**
     * Indicate the guild has data.
     */
    public function withData(): static
    {
        return $this->state(fn (array $attributes) => [
            'data' => [
                'name' => $attributes['name'],
                'realm' => $attributes['realm'],
                'region' => $attributes['region'],
                'basic' => [
                    'achievement_points' => 12500,
                    'member_count' => 150,
                    'created_timestamp' => 1609459200000,
                    'faction' => 'Alliance',
                ],
                'roster' => [],
            ],
            'data_fetched_at' => now(),
            'fetch_status' => 'complete',
        ]);
    }

    /**
     * Indicate the guild is classic.
     */
    public function classic(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_classic' => true,
        ]);
    }

    /**
     * Indicate the guild has a pending fetch.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'fetch_status' => 'pending',
            'fetch_queued_at' => now(),
        ]);
    }

    /**
     * Indicate the guild fetch failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'fetch_status' => 'failed',
        ]);
    }
}
