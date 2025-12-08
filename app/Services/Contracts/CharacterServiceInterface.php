<?php

namespace App\Services\Contracts;

interface CharacterServiceInterface
{
    /**
     * Get character profile data.
     *
     * @param  string  $region The game region
     * @param  string  $realmName The realm name
     * @param  string  $characterName The character name
     * @param  bool  $isClassic Whether to fetch classic character data
     * @return array The character profile data
     */
    public function getCharacter(string $region, string $realmName, string $characterName, bool $isClassic = false): array;
}
