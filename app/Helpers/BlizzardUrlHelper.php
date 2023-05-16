<?php

function getBlizzardApiUrl(string $region) {
    return str_replace('{region}', $region, config('blizzard.api.url'));
}

function getBlizzardOauthUrl(string $region) {
    return str_replace('{region}', $region, config('blizzard.oauth.url'));
}


