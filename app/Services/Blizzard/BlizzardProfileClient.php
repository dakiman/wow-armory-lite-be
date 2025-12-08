<?php

namespace App\Services\Blizzard;

use App\Exceptions\BlizzardServiceException;
use App\Exceptions\RateLimitException;
use App\Helpers\BlizzardUrlBuilder;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\Utils;

class BlizzardProfileClient
{
    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get guild information from Blizzard API.
     *
     * @return array{basic: \GuzzleHttp\Psr7\Response, roster: \GuzzleHttp\Psr7\Response}
     *
     * @throws RateLimitException
     * @throws BlizzardServiceException
     */
    public function getGuildInfo(string $region, string $realmName, string $guildName, bool $isClassic = false): array
    {
        $client = $this->buildClient($region, $isClassic);

        $promises = [
            'basic' => $client->getAsync("/data/wow/guild/$realmName/$guildName"),
            'roster' => $client->getAsync("/data/wow/guild/$realmName/$guildName/roster"),
        ];

        try {
            return Utils::unwrap($promises);
        } catch (Exception $e) {
            $this->handleException($e, "Couldn't retrieve guild");
        }
    }

    /**
     * Get character information from Blizzard API.
     *
     * @return array{basic: \GuzzleHttp\Psr7\Response, media: \GuzzleHttp\Psr7\Response, equipment: \GuzzleHttp\Psr7\Response, specialization: \GuzzleHttp\Psr7\Response}
     *
     * @throws RateLimitException
     * @throws BlizzardServiceException
     */
    public function getCharacterInfo(string $region, string $realmName, string $characterName, bool $isClassic = false): array
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
            $this->handleException($e, "Couldn't retrieve character $characterName @ $realmName | $region");
        }
    }

    /**
     * Get mythic keystone information from Blizzard API.
     *
     * @throws RateLimitException
     * @throws BlizzardServiceException
     */
    public function getMythicsInfo(string $region, string $realmName, string $characterName, int $season): \GuzzleHttp\Psr7\Response
    {
        $client = $this->buildClient($region);

        try {
            return $client->get("/profile/wow/character/$realmName/$characterName/mythic-keystone-profile/season/$season");
        } catch (Exception $e) {
            $this->handleException($e, "Couldn't retrieve mythics data $characterName @ $realmName | $region");
        }
    }

    /**
     * Get raiding information from Blizzard API.
     *
     * @throws RateLimitException
     * @throws BlizzardServiceException
     */
    public function getRaidingInfo(string $region, string $realmName, string $characterName): \GuzzleHttp\Psr7\Response
    {
        $client = $this->buildClient($region);

        try {
            return $client->get("/profile/wow/character/$realmName/$characterName/encounters/raids");
        } catch (Exception $e) {
            $this->handleException($e, "Couldn't retrieve raiding data $characterName @ $realmName | $region");
        }
    }

    /**
     * Build a Guzzle client for the specified region.
     */
    private function buildClient(string $region, bool $isClassic = false): Client
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

    /**
     * Handle exceptions from API calls, detecting rate limits.
     *
     * @return never
     *
     * @throws RateLimitException
     * @throws BlizzardServiceException
     */
    private function handleException(Exception $e, string $message): void
    {
        // Check for rate limit (429) responses
        if ($e instanceof ClientException && $e->getResponse()->getStatusCode() === 429) {
            $retryAfter = $e->getResponse()->getHeaderLine('Retry-After');
            throw new RateLimitException(
                'Blizzard API rate limit exceeded',
                $retryAfter ? (int) $retryAfter : null
            );
        }

        // Check for wrapped exceptions in promise results
        $previous = $e->getPrevious();
        if ($previous instanceof ClientException && $previous->getResponse()->getStatusCode() === 429) {
            $retryAfter = $previous->getResponse()->getHeaderLine('Retry-After');
            throw new RateLimitException(
                'Blizzard API rate limit exceeded',
                $retryAfter ? (int) $retryAfter : null
            );
        }

        throw new BlizzardServiceException($message, $e, 404);
    }
}
