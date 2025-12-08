<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Guild;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopularEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_character_popular_endpoint_returns_most_searched(): void
    {
        // Create characters with different search counts
        Character::factory()->withData()->create(['search_count' => 100, 'last_searched_at' => now()]);
        Character::factory()->withData()->create(['search_count' => 50, 'last_searched_at' => now()->subHour()]);
        Character::factory()->withData()->create(['search_count' => 25, 'last_searched_at' => now()->subHours(2)]);

        $response = $this->getJson('/api/character/popular');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'most_searched' => [
                    '*' => ['id', 'region', 'realm', 'name', 'search_count', 'basic'],
                ],
                'recently_searched' => [
                    '*' => ['id', 'region', 'realm', 'name', 'last_searched_at', 'basic'],
                ],
            ]);

        // Verify ordering - most searched should be first
        $mostSearched = $response->json('most_searched');
        $this->assertEquals(100, $mostSearched[0]['search_count']);
    }

    public function test_character_popular_endpoint_returns_empty_arrays_when_no_data(): void
    {
        $response = $this->getJson('/api/character/popular');

        $response->assertStatus(200)
            ->assertJson([
                'most_searched' => [],
                'recently_searched' => [],
            ]);
    }

    public function test_character_popular_excludes_characters_without_data(): void
    {
        // Character with data should appear
        Character::factory()->withData()->create(['search_count' => 100, 'last_searched_at' => now()]);
        // Character without data should NOT appear
        Character::factory()->create(['search_count' => 200, 'data' => null, 'last_searched_at' => now()]);

        $response = $this->getJson('/api/character/popular');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('most_searched'));
        $this->assertEquals(100, $response->json('most_searched.0.search_count'));
    }

    public function test_guild_popular_endpoint_returns_most_searched(): void
    {
        Guild::factory()->withData()->create(['search_count' => 80, 'last_searched_at' => now()]);
        Guild::factory()->withData()->create(['search_count' => 40, 'last_searched_at' => now()->subHour()]);

        $response = $this->getJson('/api/guild/popular');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'most_searched' => [
                    '*' => ['id', 'region', 'realm', 'name', 'search_count', 'basic'],
                ],
                'recently_searched' => [
                    '*' => ['id', 'region', 'realm', 'name', 'last_searched_at', 'basic'],
                ],
            ]);

        $mostSearched = $response->json('most_searched');
        $this->assertEquals(80, $mostSearched[0]['search_count']);
    }

    public function test_character_status_endpoint_returns_complete_when_done(): void
    {
        $character = Character::factory()->withData()->create();

        $response = $this->getJson("/api/character/status/{$character->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'complete',
            ])
            ->assertJsonStructure([
                'status',
                'character',
            ]);
    }

    public function test_character_status_endpoint_returns_pending_when_queued(): void
    {
        $character = Character::factory()->pending()->create();

        $response = $this->getJson("/api/character/status/{$character->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'pending',
            ]);
    }

    public function test_character_status_endpoint_returns_failed_when_fetch_failed(): void
    {
        $character = Character::factory()->failed()->create();

        $response = $this->getJson("/api/character/status/{$character->id}");

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'failed',
            ]);
    }

    public function test_character_status_endpoint_returns_404_for_unknown_id(): void
    {
        $response = $this->getJson('/api/character/status/99999');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'not_found',
            ]);
    }

    public function test_guild_status_endpoint_returns_complete_when_done(): void
    {
        $guild = Guild::factory()->withData()->create();

        $response = $this->getJson("/api/guild/status/{$guild->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'complete',
            ])
            ->assertJsonStructure([
                'status',
                'guild',
            ]);
    }

    public function test_guild_status_endpoint_returns_pending_when_queued(): void
    {
        $guild = Guild::factory()->pending()->create();

        $response = $this->getJson("/api/guild/status/{$guild->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'pending',
            ]);
    }

    public function test_guild_status_endpoint_returns_404_for_unknown_id(): void
    {
        $response = $this->getJson('/api/guild/status/99999');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'not_found',
            ]);
    }

    public function test_popular_respects_limit_configuration(): void
    {
        config(['blizzard.popular_limit' => 2]);

        Character::factory()->withData()->count(5)->create(['last_searched_at' => now()]);

        $response = $this->getJson('/api/character/popular');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('most_searched'));
        $this->assertCount(2, $response->json('recently_searched'));
    }
}
