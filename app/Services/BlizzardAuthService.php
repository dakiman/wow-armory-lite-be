<?php


namespace App\Services;

use App\Jobs\RetrieveCharacterData;
use App\Services\Blizzard\BlizzardAuthClient;
use App\Services\Blizzard\BlizzardUserClient;
use GuzzleHttp\Psr7\Response;
use Log;

class BlizzardAuthService
{
    private BlizzardAuthClient $authClient;
    private BlizzardUserClient $userClient;

    public function __construct(
        BlizzardAuthClient $authClient,
        BlizzardUserClient $userClient
    )
    {
        $this->authClient = $authClient;
        $this->userClient = $userClient;
    }

    public function refreshAndCacheAccessToken()
    {
        $authResponse = $this->authClient->getToken();

        $token = $authResponse->access_token;

        cache(['token' => $token], now()->addSeconds($authResponse->expires_in - 1000));

        return $token;
    }

    public function syncBattleNetDetails(string $region, string $code, string $redirectUri, $user)
    {
        $authResponse = $this->authClient->getOauthAccessToken($region, $code, $redirectUri);

        $token = $authResponse->access_token;

        $responses = $this->userClient->getUserInfoAndCharacters($token, $region);

        $this->saveOauthDetails($responses['oauth'], $region, $user);
        $this->retrieveCharactersForAccount($responses['characters'], $region, $user);
    }

    private function retrieveCharactersForAccount(Response $response, $region, $user)
    {
        $data = json_decode($response->getBody());
        $characters = $data->wow_accounts[0]->characters;

        foreach ($characters as $character) {
            Log::info('user', [$user]);
            RetrieveCharacterData::dispatch($region, $character->realm->slug, $character->name, $user->id);
        }
    }

    private function saveOauthDetails(Response $response, $region, $user): void
    {
        $data = json_decode($response->getBody());

        $user->bnet_sync_at = now();
        $user->bnet_id = $data->id;
        $user->bnet_tag = $data->battletag;
        $user->bnet_region = $region;
        $user->save();
    }

}
