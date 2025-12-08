<?php

namespace App\Constants;

class CacheKeys
{
    public const CHARACTER_PROFILE = 'character.{name}-{realm}-{region}-profile-{isClassic}';

    public const CHARACTER_MYTHICS = 'character.{name}-{realm}-{region}-mythics';

    public const CHARACTER_RAIDS = 'character.{name}-{realm}-{region}-raids';

    public const GUILD_PROFILE = 'guild.{name}-{realm}-{region}';

    public const REALMS = 'realms';

    public const ACCESS_TOKEN = 'token';

    /**
     * Build a cache key for character profile.
     */
    public static function characterProfile(string $characterName, string $realm, string $region, bool $isClassic = false): string
    {
        return str_replace(
            ['{name}', '{realm}', '{region}', '{isClassic}'],
            [$characterName, $realm, $region, $isClassic ? 'true' : 'false'],
            self::CHARACTER_PROFILE
        );
    }

    /**
     * Build a cache key for character mythics.
     */
    public static function characterMythics(string $characterName, string $realm, string $region): string
    {
        return str_replace(
            ['{name}', '{realm}', '{region}'],
            [$characterName, $realm, $region],
            self::CHARACTER_MYTHICS
        );
    }

    /**
     * Build a cache key for character raids.
     */
    public static function characterRaids(string $characterName, string $realm, string $region): string
    {
        return str_replace(
            ['{name}', '{realm}', '{region}'],
            [$characterName, $realm, $region],
            self::CHARACTER_RAIDS
        );
    }

    /**
     * Build a cache key for guild profile.
     */
    public static function guildProfile(string $guildName, string $realm, string $region): string
    {
        return str_replace(
            ['{name}', '{realm}', '{region}'],
            [$guildName, $realm, $region],
            self::GUILD_PROFILE
        );
    }
}
