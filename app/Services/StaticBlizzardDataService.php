<?php

namespace App\Services;

use App\Constants\CacheKeys;
use App\Services\Blizzard\BlizzardStaticDataClient;
use Illuminate\Support\Facades\Cache;

class StaticBlizzardDataService
{
    private BlizzardStaticDataClient $blizzardStaticDataClient;

    public function __construct(BlizzardStaticDataClient $blizzardStaticDataClient)
    {
        $this->blizzardStaticDataClient = $blizzardStaticDataClient;
    }

    /**
     * Get realms for a specific region.
     *
     * @param  string  $region The game region
     * @return \GuzzleHttp\Psr7\Response
     */
    public function getRealms(string $region = 'eu')
    {
        return $this->blizzardStaticDataClient->getRealms($region);
    }

    /**
     * Get all realms across all regions with caching.
     *
     * @return array The list of realms
     */
    public function getAllRealms(): array
    {
        return Cache::remember(CacheKeys::REALMS, now()->addDays(30), function () {
            $regions = config('blizzard.regions');
            $realms = [];

            foreach ($regions as $region) {
                $response = $this->getRealms($region);
                $regionRealms = json_decode($response->getBody());
                $regionRealms = $regionRealms->realms;
                foreach ($regionRealms as $realm) {
                    $realms[] = [
                        'name' => $realm->name.' ('.strtoupper($region).')',
                        'slug' => $realm->slug,
                        'region' => $region,
                    ];
                }
            }

            return $realms;
        });
    }
}
