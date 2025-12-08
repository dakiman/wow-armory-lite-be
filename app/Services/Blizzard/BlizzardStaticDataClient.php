<?php

namespace App\Services\Blizzard;

use App\Exceptions\BlizzardServiceException;
use App\Helpers\BlizzardUrlBuilder;
use Exception;
use GuzzleHttp\Client;

class BlizzardStaticDataClient
{
    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function getRealms(string $region = 'eu')
    {
        $client = $this->buildClient($region);

        try {
            return $client->get('/data/wow/realm/index');
        } catch (Exception $e) {
            throw new BlizzardServiceException('Couldnt retrieve realms data', $e, 404);
        }
    }

    private function buildClient(string $region)
    {
        return new Client([
            'headers' => ['Authorization' => 'Bearer '.$this->token],
            'base_uri' => BlizzardUrlBuilder::api($region),
            'query' => [
                'namespace' => 'dynamic-'.$region,
                'locale' => 'en_GB',
                'region' => $region,
            ],
        ]);
    }
}
