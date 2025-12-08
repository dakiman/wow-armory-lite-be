<?php

namespace App\Services;

use App\Exceptions\RateLimitException;
use App\Http\Responses\PendingResponse;
use App\Jobs\FetchGuildDataJob;
use App\Models\Guild;
use App\Services\Blizzard\BlizzardProfileClient;
use App\Services\Contracts\GuildServiceInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;

class GuildService implements GuildServiceInterface
{
    public function __construct(
        private BlizzardProfileClient $profileClient
    ) {
    }

    /**
     * Get guild information.
     *
     * Returns guild data immediately if available and fresh,
     * or queues a background job if rate limited.
     *
     * @return array<string, mixed>|PendingResponse
     */
    public function getGuild(string $region, string $realmName, string $guildName, bool $isClassic = false): array|PendingResponse
    {
        $realmName = Str::slug($realmName);
        $guildName = Str::slug($guildName);

        // Find or create the guild record
        $guild = Guild::findOrCreateByIdentifiers($region, $realmName, $guildName, $isClassic);

        // Track the search
        $guild->recordSearch();

        // If we have fresh data, return it immediately
        if ($guild->hasFreshData()) {
            return $guild->data;
        }

        // Try to fetch data synchronously
        try {
            $data = $this->fetchAndMapData($region, $realmName, $guildName, $isClassic);
            $guild->markAsComplete($data);

            return $data;
        } catch (RateLimitException $e) {
            // Rate limited - queue a background job
            if (! $guild->isFetchInProgress()) {
                $guild->markAsPending();
                FetchGuildDataJob::dispatch($guild);
            }

            // If we have stale data, return it with a note
            if ($guild->data !== null) {
                return $guild->data;
            }

            return new PendingResponse(
                entity: $guild,
                estimatedWait: $e->getRetryAfter() ?? 30
            );
        }
    }

    /**
     * Get popular guilds.
     *
     * @return array{most_searched: array, recently_searched: array}
     */
    public function getPopular(): array
    {
        $limit = config('blizzard.popular_limit', 5);

        return [
            'most_searched' => Guild::mostSearched($limit)
                ->get()
                ->map(fn (Guild $g) => $g->getSummary())
                ->toArray(),
            'recently_searched' => Guild::recentlySearched($limit)
                ->get()
                ->map(fn (Guild $g) => $g->getSummary())
                ->toArray(),
        ];
    }

    /**
     * Get guild by ID for status polling.
     */
    public function getGuildById(int $id): ?Guild
    {
        return Guild::find($id);
    }

    /**
     * Fetch and map guild data from Blizzard API.
     *
     * @return array<string, mixed>
     */
    private function fetchAndMapData(string $region, string $realmName, string $guildName, bool $isClassic): array
    {
        $responses = $this->profileClient->getGuildInfo($region, $realmName, $guildName, $isClassic);

        return [
            'name' => $guildName,
            'realm' => $realmName,
            'region' => $region,
            'basic' => $this->mapBasicData($responses['basic']),
            'roster' => $this->mapRosterData($responses['roster']),
        ];
    }

    /**
     * Map basic guild data from API response.
     */
    private function mapBasicData(Response $response): array
    {
        $basicData = json_decode($response->getBody());

        return [
            'achievement_points' => $basicData->achievement_points,
            'member_count' => $basicData->member_count,
            'created_timestamp' => $basicData->created_timestamp,
            'faction' => $basicData->faction->name,
        ];
    }

    /**
     * Map guild roster data from API response.
     */
    private function mapRosterData(Response $response): array
    {
        $roster = json_decode($response->getBody());

        return collect($roster->members)->map(function ($member) {
            $character = $member->character;

            return [
                'name' => $character->name,
                'realm' => $character->realm->slug,
                'level' => $character->level,
                'class' => $character->playable_class->id,
                'race' => $character->playable_race->id,
                'rank' => $member->rank,
            ];
        })->toArray();
    }
}
