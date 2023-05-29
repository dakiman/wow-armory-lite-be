<?php

return [

    'client' => [
        'id' => env('BLIZZARD_CLIENT_ID', null),
        'secret' => env('BLIZZARD_CLIENT_SECRET', null)
    ],

    'oauth' => [
        'url' => "https://{region}.battle.net",
    ],

    'api' => [
        'url' => "https://{region}.api.blizzard.com"
    ],

    'regions' => [
        'EU', 'US', 'AU', 'CH'
    ],

    'character_min_seconds_update' => env('CHARACTER_MIN_SECONDS_UPDATE', 0),

    'guild_min_seconds_update' => env('GUILD_MIN_SECONDS_UPDATE', 0),

    'min_level_for_character_lookup' => env('MIN_LEVEL_FOR_CHARACTER_LOOKUP', 0),

    'current_mythics_season' => 10

];
