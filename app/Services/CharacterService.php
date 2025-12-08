<?php

namespace App\Services;

use App\Constants\CacheKeys;
use App\Services\Blizzard\BlizzardProfileClient;
use App\Services\Contracts\CharacterServiceInterface;
use App\Services\Traits\MapsCharacterData;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CharacterService implements CharacterServiceInterface
{
    use MapsCharacterData;

    private BlizzardProfileClient $profileClient;

    public function __construct(BlizzardProfileClient $profileClient)
    {
        $this->profileClient = $profileClient;
    }

    /**
     * Get character profile data with caching.
     *
     * @param  string  $region The game region
     * @param  string  $realmName The realm name
     * @param  string  $characterName The character name
     * @param  bool  $isClassic Whether to fetch classic character data
     * @return array The character profile data
     */
    public function getCharacter(string $region, string $realmName, string $characterName, bool $isClassic = false): array
    {
        $realmName = Str::slug($realmName);
        $characterName = mb_strtolower($characterName);

        $cacheKey = CacheKeys::characterProfile($characterName, $realmName, $region, $isClassic);
        $cacheTtl = config('blizzard.character_min_seconds_update', 3600);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($region, $realmName, $characterName, $isClassic) {
            $responses = $this->profileClient->getCharacterInfo($region, $realmName, $characterName, $isClassic);

            return [
                'name' => $characterName,
                'realm' => $realmName,
                'region' => $region,
                'basic' => $this->mapBasicResponseData($responses['basic'], true),
                'media' => $this->mapMediaResponseData($responses['media']),
                'equipment' => $this->mapEquipmentResponseData($responses['equipment']),
                'specialization' => $this->mapSpecializationsResponseData($responses['specialization']),
            ];
        });
    }

    /**
     * Map equipment response data.
     */
    private function mapEquipmentResponseData(Response $response): array
    {
        $data = json_decode($response->getBody());

        return array_map(function ($equipped) {
            return [
                'id' => $equipped->item->id,
                'itemLevel' => $equipped->level->value,
                'quality' => $equipped->quality->name,
                'slot' => $equipped->slot->name,
                'bonus' => $equipped->bonus_list ?? null,
                'sockets' => $this->mapSockets($equipped),
                'set' => $this->mapSet($equipped),
                'enchantments' => $this->mapEnchantments($equipped->enchantments ?? []),
            ];
        }, $data->equipped_items);
    }

    /**
     * Map specializations response data.
     */
    private function mapSpecializationsResponseData(Response $response): array
    {
        $data = json_decode($response->getBody());

        $activeSpecName = $data->active_specialization->name;

        $activeSpec = current(array_filter($data->specializations, function ($specialization) use ($activeSpecName) {
            return $specialization->specialization->name === $activeSpecName;
        }));

        $loadout = current(array_filter($activeSpec->loadouts, function ($loadout) {
            return $loadout->is_active;
        }));

        $classTalents = $this->mapSpec($loadout->selected_class_talents);
        $specTalents = $this->mapSpec($loadout->selected_spec_talents);

        return [
            'activeSpecialization' => $activeSpecName,
            'activeSpecLoadoutCode' => $loadout->talent_loadout_code,
            'classTalents' => $classTalents,
            'specTalents' => $specTalents,
        ];
    }

    /**
     * Map item sockets.
     *
     * @param  object  $item
     */
    private function mapSockets($item): ?array
    {
        if (! isset($item->sockets) || empty($item->sockets)) {
            return null;
        }

        return array_map(function ($socket) {
            if (! isset($socket->item)) {
                return null;
            }

            return $socket->item->id;
        }, $item->sockets);
    }

    /**
     * Map item set bonuses.
     *
     * @param  object  $item
     */
    private function mapSet($item): ?array
    {
        return $this->mapSetItems($item->set ?? null);
    }

    /**
     * Map talent specialization data.
     */
    public function mapSpec(mixed $talents): array
    {
        return array_map(function ($talent) {
            return [
                'id' => $talent->id,
                'spellTooltip' => $talent?->tooltip?->spell_tooltip?->spell?->id ?? null,
                'rank' => $talent?->rank,
            ];
        }, $talents);
    }
}
