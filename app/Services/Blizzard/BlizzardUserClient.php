<?php

namespace App\Services\Blizzard;

use App\Exceptions\BlizzardServiceException;
use App\Helpers\BlizzardUrlBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;

class BlizzardUserClient
{
    /*
    * @return array [
    *      'oauth' => GuzzleHttp\Psr7\Response,
    *      'characters' => GuzzleHttp\Psr7\Response,
    *  ]
    * */
    public function getUserInfoAndCharacters(string $token, string $region)
    {
        $promises = [
            'oauth' => $this->getUserInfoRequest($token, $region),
            'characters' => $this->getUserCharactersRequest($token, $region),
        ];

        try {
            return Utils::unwrap($promises);
        } catch (\Exception $e) {
            throw new BlizzardServiceException('Had issues finishing OAuth process.', $e, 500);
        }
    }

    private function getUserInfoRequest(string $token, string $region)
    {
        $client = $this->buildClient($token, $region);

        return $client->getAsync(BlizzardUrlBuilder::oauth($region).'/oauth/userinfo');
    }

    private function getUserCharactersRequest(string $token, string $region)
    {
        $client = $this->buildClient($token, $region);

        return $client->getAsync(BlizzardUrlBuilder::api($region).'/profile/user/wow');
    }

    private function buildClient($token, $region)
    {
        return new Client([
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
            'query' => [
                'namespace' => 'profile-'.$region,
                'locale' => 'en_GB',
            ],
        ]);
    }
}
