<?php

namespace App\Services\Contracts;

interface ProgressionServiceInterface
{
    /**
     * Get character mythic keystone data.
     *
     * @param  string  $region The game region
     * @param  string  $realmName The realm name
     * @param  string  $characterName The character name
     * @return array The mythic keystone data
     */
    public function getCharacterMythics(string $region, string $realmName, string $characterName): array;

    /**
     * Get character raiding information.
     *
     * @param  string  $region The game region
     * @param  string  $realmName The realm name
     * @param  string  $characterName The character name
     * @return array The raiding data
     */
    public function getCharacterRaidingInfo(string $region, string $realmName, string $characterName): array;
}
