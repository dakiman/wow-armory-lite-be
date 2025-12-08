<?php

namespace Database\Factories;

use App\Models\Character;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Character>
 */
class CharacterFactory extends Factory
{
    protected $model = Character::class;

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
            'name' => fake()->userName(),
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
     * Indicate the character has data.
     */
    public function withData(): static
    {
        return $this->state(fn (array $attributes) => [
            'data' => [
                'name' => $attributes['name'],
                'realm' => $attributes['realm'],
                'region' => $attributes['region'],
                'basic' => [
                    'gender' => 'Male',
                    'faction' => 'Alliance',
                    'race' => 1,
                    'class' => 1,
                    'level' => 80,
                    'average_item_level' => 610,
                    'equipped_item_level' => 608,
                ],
                'media' => [
                    'avatar' => 'https://example.com/avatar.jpg',
                    'inset' => 'https://example.com/inset.jpg',
                    'main' => 'https://example.com/main.jpg',
                ],
            ],
            'data_fetched_at' => now(),
            'fetch_status' => 'complete',
        ]);
    }

    /**
     * Indicate the character is classic.
     */
    public function classic(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_classic' => true,
        ]);
    }

    /**
     * Indicate the character has a pending fetch.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'fetch_status' => 'pending',
            'fetch_queued_at' => now(),
        ]);
    }

    /**
     * Indicate the character fetch failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'fetch_status' => 'failed',
        ]);
    }
}
