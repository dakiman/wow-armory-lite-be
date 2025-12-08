<?php

namespace App\Helpers;

class BlizzardUrlBuilder
{
    /**
     * Build the Blizzard API URL for a given region.
     *
     * @param  string  $region The game region (us, eu, kr, tw)
     * @return string The API URL with region substituted
     */
    public static function api(string $region): string
    {
        return str_replace('{region}', $region, config('blizzard.api.url'));
    }

    /**
     * Build the Blizzard OAuth URL for a given region.
     *
     * @param  string  $region The game region (us, eu, kr, tw)
     * @return string The OAuth URL with region substituted
     */
    public static function oauth(string $region): string
    {
        return str_replace('{region}', $region, config('blizzard.oauth.url'));
    }
}
