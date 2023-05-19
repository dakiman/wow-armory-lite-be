<?php

namespace App\Services;

use App\DTO\Guild\GuildBasic;
use App\DTO\Guild\GuildDocument;
use App\DTO\Guild\GuildMember;
use App\Jobs\RetrieveGuildRoster;
use App\Models\Guild;
use App\Services\Blizzard\BlizzardProfileClient;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;

class GuildService
{
    private BlizzardProfileClient $profileClient;

    public function __construct(BlizzardProfileClient $profileClient)
    {
        $this->profileClient = $profileClient;
    }

    public function getGuild(string $region, string $realmName, string $guildName)
    {
        $realmName = Str::slug($realmName);
        $guildName = Str::slug($guildName);

        $responses = $this->profileClient->getGuildInfo($region, $realmName, $guildName);

        $guild = [
            'name' => $guildName,
            'realm' => $realmName,
            'region' => $region,
            'basic' => $this->mapBasicData($responses['basic']),
            'roster' => $this->mapRosterData($responses['roster']),
        ];

        return $guild;
    }

    private function mapBasicData(Response $response)
    {
        $basicData = json_decode($response->getBody());

        return [
            'achievement_points' => $basicData->achievement_points,
            'member_count' => $basicData->member_count,
            'created_timestamp' => $basicData->created_timestamp,
            'faction' => $basicData->faction->name
        ];
    }

    private function mapRosterData(Response $response)
    {
        $roster = json_decode($response->getBody());

        return collect($roster->members)->map(function ($member) {
            $character = $member->character;

            $member = [
                'name' => $character->name,
                'realm' => $character->realm->slug,
                'level' => $character->level,
                'class' => $character->playable_class->id,
                'race' => $character->playable_race->id,
                'rank' => $member->rank,
            ];
            return $member;
        })->toArray();
    }

}
