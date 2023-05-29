<?php

namespace App\Services\Blizzard;

use App\Exceptions\BlizzardServiceException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise;
use GuzzleHttp\Utils;

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
    public function getGuildInfo(string $region, string $realmName, string $guildName)
    {
        $client = $this->buildClient($region);

        $promises = [
            'basic' => $client->getAsync("/data/wow/guild/$realmName/$guildName"),
            'roster' => $client->getAsync("/data/wow/guild/$realmName/$guildName/roster"),
        ];

        try {
            return Promise\unwrap($promises);
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
    public function getCharacterInfo(string $region, string $realmName, string $characterName)
    {
        $client = $this->buildClient($region);

        $promises = [
            'basic' => $client->getAsync("/profile/wow/character/$realmName/$characterName"),
            'media' => $client->getAsync("/profile/wow/character/$realmName/$characterName/character-media"),
            'equipment' => $client->getAsync("/profile/wow/character/$realmName/$characterName/equipment"),
            'specialization' => $client->getAsync("/profile/wow/character/$realmName/$characterName/specializations")
        ];


        try {
            return Promise\unwrap($promises);
        } catch (Exception $e) {
            throw new BlizzardServiceException("Couldnt retrieve character $characterName @ $realmName | $region", $e, 404);
        }
    }

    /*
    * @return array [
    *      'best_mythics' => GuzzleHttp\Psr7\Response
    *  ]
    * */
    public function getBestMythicsInfo(string $region, string $realmName, string $characterName, int $season)
    {
        $client = $this->buildClient($region);

        try {
            return $client->get("/profile/wow/character/$realmName/$characterName/mythic-keystone-profile/season/$season");
        } catch (Exception $e) {
            throw new BlizzardServiceException("Couldnt retrieve mythics data $characterName @ $realmName | $region", $e, 404);
        }
    }

//    public function getMythicProfile(string $region, string $realmName, string $characterName) {
//        $client = $this->buildClient($region);
//
//        try {
//            return $client->get("/profile/wow/character/$realmName/$characterName/mythic-keystone-profile");
//        } catch (Exception $e) {
//            throw new BlizzardServiceException("Couldnt retrieve mythic keystone profile $characterName @ $realmName | $region", $e, 404);
//        }
//    }

    private function buildClient(string $region)
    {
        return new Client([
            'headers' => ['Authorization' => 'Bearer ' . $this->token],
            'base_uri' => getBlizzardApiUrl($region),
            'query' => [
                'namespace' => 'profile-' . $region,
                'locale' => 'en_GB'
            ]
        ]);
    }

}
