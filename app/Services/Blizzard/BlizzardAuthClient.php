<?php


namespace App\Services\Blizzard;

use App\Exceptions\BlizzardServiceException;
use GuzzleHttp\Client;

class BlizzardAuthClient
{
    private string $clientId;
    private string $clientSecret;

    public function __construct($clientId, $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new BlizzardServiceException('Blizzard client id/secret not found.');
        }
    }

    /*For now defaulting to EU seems to work for all regions, and is easiest for code structure*/
    public function getToken($region = 'eu'): object
    {
        $client = $this->buildClient($region);

        try {
            $response = $client->post('/oauth/token', [
                'form_params' => ['grant_type' => 'client_credentials'],
            ]);
        } catch (\Exception $e) {
            throw new BlizzardServiceException('Couldnt retrieve token for communication with Blizzard services.', $e);
        }

        return json_decode($response->getBody());
    }

    public function getOauthAccessToken(string $region, string $authCode, string $redirectUri)
    {
        $client = $this->buildClient($region);

        try {
            $response = $client->post('/oauth/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirectUri,
                    'code' => $authCode
                ],
            ]);
        } catch (\Exception $e) {
            throw new BlizzardServiceException('Couldnt complete Oauth authorization, please try again later', $e);
        }

        return json_decode($response->getBody());
    }

    private function buildClient(string $region)
    {
        return new Client([
            'base_uri' => getBlizzardOauthUrl($region),
            'auth' => [$this->clientId, $this->clientSecret],
        ]);
    }

}
