<?php

namespace App\Services\Contracts;

use App\Http\Responses\PendingResponse;

interface GuildServiceInterface
{
    /**
     * Get guild information.
     *
     * @param  string  $region The game region
     * @param  string  $realmName The realm name
     * @param  string  $guildName The guild name
     * @param  bool  $isClassic Whether to fetch classic guild data
     * @return array<string, mixed>|PendingResponse The guild data or pending response if rate limited
     */
    public function getGuild(string $region, string $realmName, string $guildName, bool $isClassic = false): array|PendingResponse;
}
