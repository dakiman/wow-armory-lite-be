<?php

namespace App\Services\Contracts;

use App\Http\Responses\PendingResponse;

interface CharacterServiceInterface
{
    /**
     * Get character profile data.
     *
     * @param  string  $region The game region
     * @param  string  $realmName The realm name
     * @param  string  $characterName The character name
     * @param  bool  $isClassic Whether to fetch classic character data
     * @return array<string, mixed>|PendingResponse The character profile data or pending response if rate limited
     */
    public function getCharacter(string $region, string $realmName, string $characterName, bool $isClassic = false): array|PendingResponse;
}
