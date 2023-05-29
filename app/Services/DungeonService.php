<?php

namespace App\Services;

use App\Services\Blizzard\BlizzardProfileClient;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;

class DungeonService
{
    private BlizzardProfileClient $profileClient;

    public function __construct(BlizzardProfileClient $profileClient)
    {
        $this->profileClient = $profileClient;
    }

    public function getCharacterMythicData(string $region, string $realmName, string $characterName)
    {
        $realmName = Str::slug($realmName);
        $characterName = mb_strtolower($characterName);

        $season = config('blizzard.current_mythics_season');
        $mythicsResponse = $this->profileClient->getBestMythicsInfo($region, $realmName, $characterName, $season);

        $mythicsData = json_decode($mythicsResponse->getBody());

        return [
            'general' => $this->mapCharacterMythicData($mythicsData),
            'best_runs' => $this->mapCharacterBestRuns($mythicsData)
        ];
    }

    private function mapCharacterMythicData(object $data)
    {
        return [
            'mythic_rating' => $data->mythic_rating->rating,
            'mythic_rating_color' => $data->mythic_rating->color,
        ];
    }

    private function mapCharacterBestRuns(object $data)
    {
        return array_map(function ($dungeonRun) {
            return [
                'dungeon' => $dungeonRun->dungeon->name,
                'mythic_level' => $dungeonRun->keystone_level,
                'completed_at' => $dungeonRun->completed_timestamp,
                'duration' => $dungeonRun->duration,
                'is_completed_within_time' => $dungeonRun->is_completed_within_time,
                'score' => $dungeonRun->mythic_rating->rating,
                'dungeon_rating' => $dungeonRun->map_rating->rating,
                'affixes' => $this->mapAffixes($dungeonRun)
            ];
        }, $data->best_runs);
    }

    /**
     * @param mixed $dungeonRun
     * @return array|array[]
     */
    function mapAffixes(mixed $dungeonRun): array
    {
        return array_map(function ($affix) {
            return [
                'name' => $affix->name,
                'id' => $affix->id
            ];
        }, $dungeonRun->keystone_affixes);
    }
}