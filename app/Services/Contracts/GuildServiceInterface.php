<?php

namespace App\Services\Contracts;

interface GuildServiceInterface
{
    /**
     * Get guild information.
     *
     * @param  string  $region The game region
     * @param  string  $realmName The realm name
     * @param  string  $guildName The guild name
     * @param  bool  $isClassic Whether to fetch classic guild data
     * @return array The guild data
     */
    public function getGuild(string $region, string $realmName, string $guildName, bool $isClassic = false): array;
}
