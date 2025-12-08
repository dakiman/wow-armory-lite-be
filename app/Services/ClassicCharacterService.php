<?php

namespace App\Services;

use App\Constants\CacheKeys;
use App\Services\Blizzard\BlizzardProfileClient;
use App\Services\Traits\MapsCharacterData;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ClassicCharacterService
{
    use MapsCharacterData;

    private BlizzardProfileClient $profileClient;

    public function __construct(BlizzardProfileClient $profileClient)
    {
        $this->profileClient = $profileClient;
    }

    /**
     * Get classic character profile data with caching.
     *
     * @param  string  $region The game region
     * @param  string  $realmName The realm name
     * @param  string  $characterName The character name
     * @return array The character profile data
     */
    public function getCharacter(string $region, string $realmName, string $characterName): array
    {
        $realmName = Str::slug($realmName);
        $characterName = mb_strtolower($characterName);

        $cacheKey = CacheKeys::characterProfile($characterName, $realmName, $region, true);
        $cacheTtl = config('blizzard.character_min_seconds_update', 3600);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($region, $realmName, $characterName) {
            $responses = $this->profileClient->getCharacterInfo($region, $realmName, $characterName, true);

            return [
                'name' => $characterName,
                'realm' => $realmName,
                'region' => $region,
                'basic' => $this->mapBasicResponseData($responses['basic']),
                'media' => $this->mapMediaResponseData($responses['media']),
                'equipment' => $this->mapEquipmentResponse($responses['equipment']),
                'specialization' => $this->mapSpecializationResponse($responses['specialization']),
            ];
        });
    }

    private function mapSpecializationResponse(Response $response)
    {
        $data = json_decode($response->getBody());

        // Find active specialization group and spec
        $activeGroup = collect($data->specialization_groups ?? [])
            ->first(fn ($group) => $group->is_active);

        if (! $activeGroup) {
            return [
                'activeSpecialization' => null,
                'activeSpecLoadoutCode' => '',
                'classTalents' => [],
                'specTalents' => [],
            ];
        }

        $activeSpec = $this->findActiveSpecialization($activeGroup->specializations ?? []);

        return [
            'activeSpecialization' => $activeSpec?->specialization_name,
            'activeSpecLoadoutCode' => $data->_links->self->href ?? '',
            'classTalents' => $this->mapClassTalents($activeGroup->specializations ?? [], $activeSpec?->specialization_name ?? ''),
            'specTalents' => $this->mapSpecTalents($activeSpec?->talents ?? []),
        ];
    }

    private function findActiveSpecialization(array $specializations)
    {
        return collect($specializations)
            ->sortByDesc('spent_points')
            ->first();
    }

    private function mapClassTalents(array $specializations, string $activeSpecName): array
    {
        return collect($specializations)
            ->filter(fn ($spec) => $spec->specialization_name !== $activeSpecName)
            ->flatMap(fn ($spec) => $this->mapTalents($spec->talents ?? []))
            ->values()
            ->all();
    }

    private function mapSpecTalents(array $talents): array
    {
        return $this->mapTalents($talents);
    }

    private function mapTalents(array $talents): array
    {
        return collect($talents)
            ->map(fn ($talent) => [
                'id' => $talent->talent->id ?? null,
                'spellTooltip' => $talent->spell_tooltip->spell->id ?? null,
                'rank' => $talent->talent_rank ?? null,
            ])
            ->all();
    }

    private function mapEquipmentResponse(Response $response)
    {
        $data = json_decode($response->getBody());

        return array_map(fn ($item) => $this->transformItem($item), $data->equipped_items);
    }

    private function transformItem($item)
    {
        return [
            'id' => $item->item->id,
            'quality' => $item->quality->name,
            'slot' => $item->slot->name,
            'set' => $this->mapSetItems($item->set ?? null),
            'enchantments' => $this->mapEnchantments($item->enchantments ?? []),
            'runes' => $this->mapRunes($item->enchantments ?? []),
        ];
    }

    private function mapRunes($enchantments)
    {
        $runes = array_filter($enchantments, function ($enchantment) {
            return $enchantment->enchantment_slot->type === 'TEMPORARY';
        });

        return array_map(function ($enchantment) {
            return [
                'name' => $enchantment->display_string,
                'id' => $enchantment->enchantment_id,
            ];
        }, $runes);
    }
}
