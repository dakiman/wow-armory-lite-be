<?php

namespace App\Services;

use App\Constants\CacheKeys;
use App\Exceptions\BlizzardServiceException;
use App\Services\Blizzard\BlizzardProfileClient;
use App\Services\Contracts\ProgressionServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProgressionService implements ProgressionServiceInterface
{
    private BlizzardProfileClient $profileClient;

    public function __construct(BlizzardProfileClient $profileClient)
    {
        $this->profileClient = $profileClient;
    }

    /**
     * Get character mythic keystone data with caching.
     *
     * @param  string  $region The game region
     * @param  string  $realmName The realm name
     * @param  string  $characterName The character name
     * @return array The mythic keystone data
     */
    public function getCharacterMythics(string $region, string $realmName, string $characterName): array
    {
        $realmName = Str::slug($realmName);
        $characterName = mb_strtolower($characterName);

        $cacheKey = CacheKeys::characterMythics($characterName, $realmName, $region);
        $cacheTtl = config('blizzard.character_min_seconds_update', 3600);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($region, $realmName, $characterName) {
            $season = config('blizzard.current_mythics_season');
            $mythicsResponse = $this->profileClient->getMythicsInfo($region, $realmName, $characterName, $season);

            $mythicsData = json_decode($mythicsResponse->getBody());

            return [
                'mythic_dungeons' => [
                    'general' => $this->mapCharacterMythicData($mythicsData),
                    'best_runs' => $this->mapCharacterBestRuns($mythicsData),
                ],
            ];
        });
    }

    /**
     * Get character raiding information with caching.
     *
     * @param  string  $region The game region
     * @param  string  $realmName The realm name
     * @param  string  $characterName The character name
     * @return array The raiding data
     */
    public function getCharacterRaidingInfo(string $region, string $realmName, string $characterName): array
    {
        $realmName = Str::slug($realmName);
        $characterName = mb_strtolower($characterName);

        $cacheKey = CacheKeys::characterRaids($characterName, $realmName, $region);
        $cacheTtl = config('blizzard.character_min_seconds_update', 3600);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($region, $realmName, $characterName) {
            $raidingResponse = $this->profileClient->getRaidingInfo($region, $realmName, $characterName);
            $raidsData = json_decode($raidingResponse->getBody());

            return [
                'raids' => $this->mapRaidData($raidsData),
            ];
        });
    }

    /**
     * Map general mythic rating data.
     */
    private function mapCharacterMythicData(object $data): array
    {
        return [
            'mythic_rating' => $data->mythic_rating->rating,
            'mythic_rating_color' => $data->mythic_rating->color,
        ];
    }

    /**
     * Map best mythic dungeon runs.
     */
    private function mapCharacterBestRuns(object $data): array
    {
        return array_map(function ($dungeonRun) {
            return [
                'dungeon' => $dungeonRun->dungeon->name,
                'mythic_level' => $dungeonRun->keystone_level,
                'completed_at' => $dungeonRun->completed_timestamp,
                'duration' => $dungeonRun->duration,
                'is_completed_within_time' => $dungeonRun->is_completed_within_time,
                'score' => $dungeonRun->mythic_rating->rating,
                'affixes' => $this->mapAffixes($dungeonRun),
            ];
        }, $data->best_runs);
    }

    /**
     * Map keystone affixes from dungeon run data.
     */
    private function mapAffixes(object $dungeonRun): array
    {
        return array_map(function ($affix) {
            return [
                'name' => $affix->name,
                'id' => $affix->id,
            ];
        }, $dungeonRun->keystone_affixes);
    }

    /**
     * Map raid progression data.
     *
     * @throws BlizzardServiceException
     */
    private function mapRaidData(object $raidsData): array
    {
        $raids = array_filter($raidsData->expansions, function ($expansionRaids) {
            return $expansionRaids->expansion->name === 'Dragonflight';
        });

        if (empty($raids)) {
            throw new BlizzardServiceException('Couldnt retrieve raiding data', null, 404);
        }

        $data = ([...$raids][0])->instances;

        return array_map(function ($raidRun) {
            return [
                'name' => $raidRun->instance->name,
                'id' => $raidRun->instance->id,
                'modes' => $this->mapModes($raidRun->modes),
            ];
        }, $data);
    }

    /**
     * Map raid difficulty modes.
     */
    private function mapModes(array $modes): array
    {
        return array_map(function ($modeRun) {
            return [
                'mode' => $modeRun->difficulty->name,
                'status' => $modeRun->status->name,
                'progress' => [
                    'total' => $modeRun->progress->total_count,
                    'completed' => $modeRun->progress->completed_count,
                ],
                'encounters' => $this->mapEncounters($modeRun->progress->encounters),
            ];
        }, $modes);
    }

    /**
     * Map raid encounter data.
     */
    private function mapEncounters(array $encounters): array
    {
        return array_map(function ($encounter) {
            return [
                'name' => $encounter->encounter->name,
                'completed_count' => $encounter->completed_count,
                'last_kill' => $encounter->last_kill_timestamp,
            ];
        }, $encounters);
    }
}
