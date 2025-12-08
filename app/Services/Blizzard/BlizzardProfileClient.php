<?php

namespace App\Services\Blizzard;

use App\Exceptions\BlizzardServiceException;
use App\Helpers\BlizzardUrlBuilder;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;

class BlizzardProfileClient
{
    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /*
     * @return array [
     *      'basic' => GuzzleHttp\Psr7\Response,
     *      'roster' => GuzzleHttp\Psr7\Response,
     *  ]
     * */
    public function getGuildInfo(string $region, string $realmName, string $guildName, bool $isClassic = false)
    {
        $client = $this->buildClient($region, $isClassic);

        $promises = [
            'basic' => $client->getAsync("/data/wow/guild/$realmName/$guildName"),
            'roster' => $client->getAsync("/data/wow/guild/$realmName/$guildName/roster"),
        ];

        try {
            return Utils::unwrap($promises);
        } catch (Exception $e) {
            throw new BlizzardServiceException('Couldnt retrieve guild', $e, 404);
        }
    }

    /*
    * @return array [
    *      'basic' => GuzzleHttp\Psr7\Response,
    *      'media' => GuzzleHttp\Psr7\Response,
    *      'equipment' => GuzzleHttp\Psr7\Response
    *  ]
    * */
    public function getCharacterInfo(string $region, string $realmName, string $characterName, bool $isClassic = false)
    {
        $client = $this->buildClient($region, $isClassic);

        $promises = [
            'basic' => $client->getAsync("/profile/wow/character/$realmName/$characterName"),
            'media' => $client->getAsync("/profile/wow/character/$realmName/$characterName/character-media"),
            'equipment' => $client->getAsync("/profile/wow/character/$realmName/$characterName/equipment"),
            'specialization' => $client->getAsync("/profile/wow/character/$realmName/$characterName/specializations"),
        ];

        try {
            return Utils::unwrap($promises);
        } catch (Exception $e) {
            throw new BlizzardServiceException("Couldnt retrieve character $characterName @ $realmName | $region", $e, 404);
        }
    }

    public function getMythicsInfo(string $region, string $realmName, string $characterName, int $season)
    {
        $client = $this->buildClient($region);

        try {
            return $client->get("/profile/wow/character/$realmName/$characterName/mythic-keystone-profile/season/$season");
        } catch (Exception $e) {
            throw new BlizzardServiceException("Couldnt retrieve mythics data $characterName @ $realmName | $region", $e, 404);
        }
    }

    public function getRaidingInfo(string $region, string $realmName, string $characterName)
    {
        $client = $this->buildClient($region);

        try {
            return $client->get("/profile/wow/character/$realmName/$characterName/encounters/raids");
        } catch (Exception $e) {
            throw new BlizzardServiceException("Couldnt retrieve raiding data $characterName @ $realmName | $region", $e, 404);
        }
    }

    private function buildClient(string $region, bool $isClassic = false)
    {
        return new Client([
            'headers' => ['Authorization' => 'Bearer '.$this->token],
            'base_uri' => BlizzardUrlBuilder::api($region),
            'query' => [
                'namespace' => $isClassic ? 'profile-classic1x-'.$region : 'profile-'.$region,
                'locale' => 'en_GB',
            ],
        ]);
    }
}
