<?php

namespace App\Services;

use App\Constants\CacheKeys;
use App\Services\Blizzard\BlizzardProfileClient;
use App\Services\Contracts\GuildServiceInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GuildService implements GuildServiceInterface
{
    private BlizzardProfileClient $profileClient;

    public function __construct(BlizzardProfileClient $profileClient)
    {
        $this->profileClient = $profileClient;
    }

    /**
     * Get guild information with caching.
     *
     * @param  string  $region The game region
     * @param  string  $realmName The realm name
     * @param  string  $guildName The guild name
     * @param  bool  $isClassic Whether to fetch classic guild data
     * @return array The guild data
     */
    public function getGuild(string $region, string $realmName, string $guildName, bool $isClassic = false): array
    {
        $realmName = Str::slug($realmName);
        $guildName = Str::slug($guildName);

        $cacheKey = CacheKeys::guildProfile($guildName, $realmName, $region);
        $cacheTtl = config('blizzard.guild_min_seconds_update', 3600);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($region, $realmName, $guildName, $isClassic) {
            $responses = $this->profileClient->getGuildInfo($region, $realmName, $guildName, $isClassic);

            return [
                'name' => $guildName,
                'realm' => $realmName,
                'region' => $region,
                'basic' => $this->mapBasicData($responses['basic']),
                'roster' => $this->mapRosterData($responses['roster']),
            ];
        });
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
