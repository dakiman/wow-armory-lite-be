<?php


namespace App\Services\Blizzard;

use App\Exceptions\BlizzardServiceException;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class BlizzardUserClient {

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
            'characters' => $this->getUserCharactersRequest($token, $region)
        ];

        try {
            return Promise\unwrap($promises);
        } catch (\Exception $e) {
            throw new BlizzardServiceException('Had issues finishing OAuth process.', $e, 500);
        }
    }

    private function getUserInfoRequest(string $token, string $region)
    {
        $client = $this->buildClient($token, $region);

        return $client->getAsync(getBlizzardOauthUrl($region) . '/oauth/userinfo');
    }

    private function getUserCharactersRequest(string $token, string $region)
    {
        $client = $this->buildClient($token, $region);

        return $client->getAsync(getBlizzardApiUrl($region) . '/profile/user/wow');
    }

    private function buildClient($token, $region)
    {
        return new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ],
            'query' => [
                'namespace' => 'profile-' . $region,
                'locale' => 'en_GB'
            ]
        ]);
    }

}
