<?php

namespace Tests\Unit;

use App\Models\Character;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_data_stale_returns_true_when_never_fetched(): void
    {
        $character = Character::factory()->create([
            'data_fetched_at' => null,
        ]);

        $this->assertTrue($character->isDataStale());
    }

    public function test_is_data_stale_returns_true_when_fetched_before_valid_after_date(): void
    {
        config(['blizzard.data_valid_after' => '2025-06-01']);

        $character = Character::factory()->create([
            'data_fetched_at' => Carbon::parse('2025-05-01'),
        ]);

        $this->assertTrue($character->isDataStale());
    }

    public function test_is_data_stale_returns_true_when_ttl_expired(): void
    {
        config(['blizzard.data_valid_after' => '2025-01-01']);
        config(['blizzard.character_min_seconds_update' => 3600]);

        $character = Character::factory()->create([
            'data_fetched_at' => now()->subHours(2),
        ]);

        $this->assertTrue($character->isDataStale());
    }

    public function test_is_data_stale_returns_false_when_data_is_fresh(): void
    {
        config(['blizzard.data_valid_after' => '2025-01-01']);
        config(['blizzard.character_min_seconds_update' => 3600]);

        $character = Character::factory()->create([
            'data_fetched_at' => now()->subMinutes(30),
        ]);

        $this->assertFalse($character->isDataStale());
    }

    public function test_has_fresh_data_returns_true_with_fresh_data(): void
    {
        config(['blizzard.data_valid_after' => '2025-01-01']);
        config(['blizzard.character_min_seconds_update' => 3600]);

        $character = Character::factory()->create([
            'data' => ['basic' => ['level' => 80]],
            'data_fetched_at' => now()->subMinutes(30),
        ]);

        $this->assertTrue($character->hasFreshData());
    }

    public function test_has_fresh_data_returns_false_when_data_is_null(): void
    {
        $character = Character::factory()->create([
            'data' => null,
            'data_fetched_at' => now(),
        ]);

        $this->assertFalse($character->hasFreshData());
    }

    public function test_record_search_increments_count_and_updates_timestamp(): void
    {
        $character = Character::factory()->create([
            'search_count' => 5,
            'last_searched_at' => null,
        ]);

        $character->recordSearch();

        $this->assertEquals(6, $character->fresh()->search_count);
        $this->assertNotNull($character->fresh()->last_searched_at);
    }

    public function test_mark_as_pending_updates_status_and_timestamp(): void
    {
        $character = Character::factory()->create([
            'fetch_status' => 'idle',
            'fetch_queued_at' => null,
        ]);

        $character->markAsPending();

        $this->assertEquals('pending', $character->fresh()->fetch_status);
        $this->assertNotNull($character->fresh()->fetch_queued_at);
    }

    public function test_mark_as_complete_stores_data_and_updates_status(): void
    {
        $character = Character::factory()->create([
            'fetch_status' => 'fetching',
            'data' => null,
        ]);

        $data = ['basic' => ['level' => 80, 'class' => 1]];
        $character->markAsComplete($data);

        $character->refresh();
        $this->assertEquals('complete', $character->fetch_status);
        $this->assertEquals($data, $character->data);
        $this->assertNotNull($character->data_fetched_at);
    }

    public function test_is_fetch_in_progress_returns_true_for_pending(): void
    {
        $character = Character::factory()->create(['fetch_status' => 'pending']);

        $this->assertTrue($character->isFetchInProgress());
    }

    public function test_is_fetch_in_progress_returns_true_for_fetching(): void
    {
        $character = Character::factory()->create(['fetch_status' => 'fetching']);

        $this->assertTrue($character->isFetchInProgress());
    }

    public function test_is_fetch_in_progress_returns_false_for_complete(): void
    {
        $character = Character::factory()->create(['fetch_status' => 'complete']);

        $this->assertFalse($character->isFetchInProgress());
    }

    public function test_find_or_create_by_identifiers_creates_new_record(): void
    {
        $character = Character::findOrCreateByIdentifiers('eu', 'tarren-mill', 'testchar', false);

        $this->assertInstanceOf(Character::class, $character);
        $this->assertEquals('eu', $character->region);
        $this->assertEquals('tarren-mill', $character->realm);
        $this->assertEquals('testchar', $character->name);
        $this->assertFalse($character->is_classic);
    }

    public function test_find_or_create_by_identifiers_finds_existing_record(): void
    {
        $existing = Character::factory()->create([
            'region' => 'eu',
            'realm' => 'tarren-mill',
            'name' => 'existingchar',
            'is_classic' => false,
        ]);

        $found = Character::findOrCreateByIdentifiers('eu', 'tarren-mill', 'existingchar', false);

        $this->assertEquals($existing->id, $found->id);
    }

    public function test_most_searched_scope_orders_by_search_count(): void
    {
        Character::factory()->create(['search_count' => 10, 'data' => ['test' => true]]);
        Character::factory()->create(['search_count' => 50, 'data' => ['test' => true]]);
        Character::factory()->create(['search_count' => 25, 'data' => ['test' => true]]);

        $results = Character::mostSearched(2)->get();

        $this->assertCount(2, $results);
        $this->assertEquals(50, $results->first()->search_count);
        $this->assertEquals(25, $results->last()->search_count);
    }

    public function test_recently_searched_scope_orders_by_last_searched_at(): void
    {
        Character::factory()->create([
            'last_searched_at' => now()->subHours(2),
            'data' => ['test' => true],
        ]);
        Character::factory()->create([
            'last_searched_at' => now()->subMinutes(30),
            'data' => ['test' => true],
        ]);
        Character::factory()->create([
            'last_searched_at' => now()->subHour(),
            'data' => ['test' => true],
        ]);

        $results = Character::recentlySearched(2)->get();

        $this->assertCount(2, $results);
        // Most recent should be first
        $this->assertTrue($results->first()->last_searched_at > $results->last()->last_searched_at);
    }

    public function test_get_summary_returns_expected_structure(): void
    {
        $character = Character::factory()->create([
            'region' => 'us',
            'realm' => 'stormrage',
            'name' => 'testchar',
            'is_classic' => false,
            'search_count' => 42,
            'last_searched_at' => now(),
            'data' => ['basic' => ['level' => 80]],
        ]);

        $summary = $character->getSummary();

        $this->assertArrayHasKey('id', $summary);
        $this->assertArrayHasKey('region', $summary);
        $this->assertArrayHasKey('realm', $summary);
        $this->assertArrayHasKey('name', $summary);
        $this->assertArrayHasKey('is_classic', $summary);
        $this->assertArrayHasKey('search_count', $summary);
        $this->assertArrayHasKey('last_searched_at', $summary);
        $this->assertArrayHasKey('basic', $summary);
        $this->assertEquals(['level' => 80], $summary['basic']);
    }
}
