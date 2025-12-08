<?php

return [

    'client' => [
        'id' => env('BLIZZARD_CLIENT_ID', null),
        'secret' => env('BLIZZARD_CLIENT_SECRET', null),
    ],

    'oauth' => [
        'url' => 'https://{region}.battle.net',
    ],

    'api' => [
        'url' => 'https://{region}.api.blizzard.com',
    ],

    'regions' => [
        'us', 'eu', /*, 'kr', 'tw'*/
    ],

    'character_min_seconds_update' => env('CHARACTER_MIN_SECONDS_UPDATE', 3600),

    'guild_min_seconds_update' => env('GUILD_MIN_SECONDS_UPDATE', 3600),

    'min_level_for_character_lookup' => env('MIN_LEVEL_FOR_CHARACTER_LOOKUP', 0),

    'current_mythics_season' => 10,

    /*
    |--------------------------------------------------------------------------
    | Data Validity Date
    |--------------------------------------------------------------------------
    |
    | Any data fetched BEFORE this date is considered stale and will be
    | re-fetched from the Blizzard API. Update this date when:
    | - A new WoW expansion releases
    | - Blizzard changes their API response structure
    | - You need to force refresh all cached data
    |
    */
    'data_valid_after' => env('BLIZZARD_DATA_VALID_AFTER', '2025-01-01'),

    /*
    |--------------------------------------------------------------------------
    | Popular Listings Configuration
    |--------------------------------------------------------------------------
    |
    | Number of items to return for "popular" and "recent" lists.
    |
    */
    'popular_limit' => env('BLIZZARD_POPULAR_LIMIT', 5),

];
