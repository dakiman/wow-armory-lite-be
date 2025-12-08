<?php

namespace Tests\Feature;

use App\Exceptions\RateLimitException;
use App\Http\Responses\PendingResponse;
use App\Jobs\FetchCharacterDataJob;
use App\Models\Character;
use App\Services\Blizzard\BlizzardProfileClient;
use App\Services\CharacterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class CharacterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_character_returns_fresh_data_without_api_call(): void
    {
        config(['blizzard.data_valid_after' => '2025-01-01']);
        config(['blizzard.character_min_seconds_update' => 3600]);

        $character = Character::factory()->withData()->create([
            'region' => 'eu',
            'realm' => 'tarren-mill',
            'name' => 'testchar',
            'data_fetched_at' => now()->subMinutes(30),
        ]);

        // Mock the profile client - it should NOT be called
        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $mockClient->shouldNotReceive('getCharacterInfo');

        $service = new CharacterService($mockClient);
        $result = $service->getCharacter('eu', 'tarren-mill', 'testchar', false);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('basic', $result);
    }

    public function test_get_character_records_search_count(): void
    {
        config(['blizzard.data_valid_after' => '2025-01-01']);
        config(['blizzard.character_min_seconds_update' => 3600]);

        $character = Character::factory()->withData()->create([
            'region' => 'eu',
            'realm' => 'tarren-mill',
            'name' => 'testchar',
            'search_count' => 5,
            'data_fetched_at' => now()->subMinutes(30),
        ]);

        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $service = new CharacterService($mockClient);

        $service->getCharacter('eu', 'tarren-mill', 'testchar', false);

        $this->assertEquals(6, $character->fresh()->search_count);
        $this->assertNotNull($character->fresh()->last_searched_at);
    }

    public function test_get_character_creates_new_record_if_not_exists(): void
    {
        Queue::fake();

        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $mockClient->shouldReceive('getCharacterInfo')
            ->once()
            ->andThrow(new RateLimitException('Rate limited', 30));

        $service = new CharacterService($mockClient);

        $this->assertDatabaseMissing('characters', [
            'region' => 'eu',
            'realm' => 'new-realm',
            'name' => 'newchar',
        ]);

        $result = $service->getCharacter('eu', 'new-realm', 'newchar', false);

        $this->assertDatabaseHas('characters', [
            'region' => 'eu',
            'realm' => 'new-realm',
            'name' => 'newchar',
        ]);
    }

    public function test_get_character_queues_job_on_rate_limit(): void
    {
        Queue::fake();

        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $mockClient->shouldReceive('getCharacterInfo')
            ->once()
            ->andThrow(new RateLimitException('Rate limited', 30));

        $service = new CharacterService($mockClient);
        $result = $service->getCharacter('eu', 'tarren-mill', 'testchar', false);

        Queue::assertPushed(FetchCharacterDataJob::class);
        $this->assertInstanceOf(PendingResponse::class, $result);
    }

    public function test_get_character_returns_stale_data_on_rate_limit_if_available(): void
    {
        Queue::fake();

        $character = Character::factory()->create([
            'region' => 'eu',
            'realm' => 'tarren-mill',
            'name' => 'testchar',
            'data' => ['basic' => ['level' => 70]], // Old stale data
            'data_fetched_at' => now()->subDays(5), // Very stale
        ]);

        config(['blizzard.data_valid_after' => '2025-01-01']);
        config(['blizzard.character_min_seconds_update' => 3600]);

        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $mockClient->shouldReceive('getCharacterInfo')
            ->once()
            ->andThrow(new RateLimitException('Rate limited', 30));

        $service = new CharacterService($mockClient);
        $result = $service->getCharacter('eu', 'tarren-mill', 'testchar', false);

        // Should return the stale data instead of PendingResponse
        $this->assertIsArray($result);
        $this->assertEquals(['level' => 70], $result['basic']);

        // Should still queue the job for background refresh
        Queue::assertPushed(FetchCharacterDataJob::class);
    }

    public function test_get_character_does_not_queue_duplicate_jobs(): void
    {
        Queue::fake();

        $character = Character::factory()->pending()->create([
            'region' => 'eu',
            'realm' => 'tarren-mill',
            'name' => 'testchar',
        ]);

        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $mockClient->shouldReceive('getCharacterInfo')
            ->once()
            ->andThrow(new RateLimitException('Rate limited', 30));

        $service = new CharacterService($mockClient);
        $service->getCharacter('eu', 'tarren-mill', 'testchar', false);

        // Should NOT queue a new job since one is already pending
        Queue::assertNotPushed(FetchCharacterDataJob::class);
    }

    public function test_get_popular_returns_correct_structure(): void
    {
        Character::factory()->withData()->create(['search_count' => 50, 'last_searched_at' => now()]);
        Character::factory()->withData()->create(['search_count' => 30, 'last_searched_at' => now()->subHour()]);

        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $service = new CharacterService($mockClient);

        $result = $service->getPopular();

        $this->assertArrayHasKey('most_searched', $result);
        $this->assertArrayHasKey('recently_searched', $result);
        $this->assertCount(2, $result['most_searched']);
        $this->assertEquals(50, $result['most_searched'][0]['search_count']);
    }

    public function test_get_character_by_id_returns_character(): void
    {
        $character = Character::factory()->create();

        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $service = new CharacterService($mockClient);

        $result = $service->getCharacterById($character->id);

        $this->assertInstanceOf(Character::class, $result);
        $this->assertEquals($character->id, $result->id);
    }

    public function test_get_character_by_id_returns_null_for_unknown(): void
    {
        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $service = new CharacterService($mockClient);

        $result = $service->getCharacterById(99999);

        $this->assertNull($result);
    }
}
