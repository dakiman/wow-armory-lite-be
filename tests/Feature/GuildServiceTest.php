<?php

namespace Tests\Feature;

use App\Exceptions\RateLimitException;
use App\Http\Responses\PendingResponse;
use App\Jobs\FetchGuildDataJob;
use App\Models\Guild;
use App\Services\Blizzard\BlizzardProfileClient;
use App\Services\GuildService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class GuildServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_guild_returns_fresh_data_without_api_call(): void
    {
        config(['blizzard.data_valid_after' => '2025-01-01']);
        config(['blizzard.guild_min_seconds_update' => 3600]);

        $guild = Guild::factory()->withData()->create([
            'region' => 'eu',
            'realm' => 'tarren-mill',
            'name' => 'test-guild',
            'data_fetched_at' => now()->subMinutes(30),
        ]);

        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $mockClient->shouldNotReceive('getGuildInfo');

        $service = new GuildService($mockClient);
        $result = $service->getGuild('eu', 'tarren-mill', 'test-guild', false);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('basic', $result);
    }

    public function test_get_guild_records_search_count(): void
    {
        config(['blizzard.data_valid_after' => '2025-01-01']);
        config(['blizzard.guild_min_seconds_update' => 3600]);

        $guild = Guild::factory()->withData()->create([
            'region' => 'eu',
            'realm' => 'tarren-mill',
            'name' => 'test-guild',
            'search_count' => 10,
            'data_fetched_at' => now()->subMinutes(30),
        ]);

        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $service = new GuildService($mockClient);

        $service->getGuild('eu', 'tarren-mill', 'test-guild', false);

        $this->assertEquals(11, $guild->fresh()->search_count);
        $this->assertNotNull($guild->fresh()->last_searched_at);
    }

    public function test_get_guild_queues_job_on_rate_limit(): void
    {
        Queue::fake();

        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $mockClient->shouldReceive('getGuildInfo')
            ->once()
            ->andThrow(new RateLimitException('Rate limited', 30));

        $service = new GuildService($mockClient);
        $result = $service->getGuild('eu', 'tarren-mill', 'test-guild', false);

        Queue::assertPushed(FetchGuildDataJob::class);
        $this->assertInstanceOf(PendingResponse::class, $result);
    }

    public function test_get_guild_returns_stale_data_on_rate_limit_if_available(): void
    {
        Queue::fake();

        $guild = Guild::factory()->create([
            'region' => 'eu',
            'realm' => 'tarren-mill',
            'name' => 'test-guild',
            'data' => ['basic' => ['member_count' => 100]],
            'data_fetched_at' => now()->subDays(5),
        ]);

        config(['blizzard.data_valid_after' => '2025-01-01']);
        config(['blizzard.guild_min_seconds_update' => 3600]);

        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $mockClient->shouldReceive('getGuildInfo')
            ->once()
            ->andThrow(new RateLimitException('Rate limited', 30));

        $service = new GuildService($mockClient);
        $result = $service->getGuild('eu', 'tarren-mill', 'test-guild', false);

        $this->assertIsArray($result);
        $this->assertEquals(['member_count' => 100], $result['basic']);

        Queue::assertPushed(FetchGuildDataJob::class);
    }

    public function test_get_guild_does_not_queue_duplicate_jobs(): void
    {
        Queue::fake();

        $guild = Guild::factory()->pending()->create([
            'region' => 'eu',
            'realm' => 'tarren-mill',
            'name' => 'test-guild',
        ]);

        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $mockClient->shouldReceive('getGuildInfo')
            ->once()
            ->andThrow(new RateLimitException('Rate limited', 30));

        $service = new GuildService($mockClient);
        $service->getGuild('eu', 'tarren-mill', 'test-guild', false);

        Queue::assertNotPushed(FetchGuildDataJob::class);
    }

    public function test_get_popular_returns_correct_structure(): void
    {
        Guild::factory()->withData()->create(['search_count' => 80, 'last_searched_at' => now()]);
        Guild::factory()->withData()->create(['search_count' => 40, 'last_searched_at' => now()->subHour()]);

        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $service = new GuildService($mockClient);

        $result = $service->getPopular();

        $this->assertArrayHasKey('most_searched', $result);
        $this->assertArrayHasKey('recently_searched', $result);
        $this->assertCount(2, $result['most_searched']);
        $this->assertEquals(80, $result['most_searched'][0]['search_count']);
    }

    public function test_get_guild_by_id_returns_guild(): void
    {
        $guild = Guild::factory()->create();

        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $service = new GuildService($mockClient);

        $result = $service->getGuildById($guild->id);

        $this->assertInstanceOf(Guild::class, $result);
        $this->assertEquals($guild->id, $result->id);
    }

    public function test_get_guild_by_id_returns_null_for_unknown(): void
    {
        $mockClient = Mockery::mock(BlizzardProfileClient::class);
        $service = new GuildService($mockClient);

        $result = $service->getGuildById(99999);

        $this->assertNull($result);
    }
}
