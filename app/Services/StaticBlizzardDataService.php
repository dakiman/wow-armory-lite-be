<?php

namespace App\Services;


use App\Services\Blizzard\BlizzardStaticDataClient;

class StaticBlizzardDataService
{
    private BlizzardStaticDataClient $blizzardStaticDataClient;


    public function __construct(BlizzardStaticDataClient $blizzardStaticDataClient)
    {
        $this->blizzardStaticDataClient = $blizzardStaticDataClient;
    }

    public function getRealms($region = 'eu')
    {
        return $this->blizzardStaticDataClient->getRealms($region);
    }

    public function getAllRealms()
    {
        $realms = cache('realms');
        if(!empty($realms)) {
            return $realms;
        }

        $regions = config('blizzard.regions');
        $realms = [];

        foreach($regions as $region) {
            $response = $this->getRealms($region);
            $regionRealms = json_decode($response->getBody());
            $regionRealms = $regionRealms->realms;
            foreach($regionRealms as $realm) {
                $realms[] = [
                    'name' => $realm->name . ' (' . strtoupper($region) . ')',
                    'slug' => $realm->slug,
                    'region' => $region,
                ];
            }
        }

        cache(['realms' => $realms], now()->addDays(30));

        return $realms;
    }

}
