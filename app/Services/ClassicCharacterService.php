<?php


namespace App\Services;

use App\Services\Blizzard\BlizzardProfileClient;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;

class ClassicCharacterService
{
    private BlizzardProfileClient $profileClient;

    public function __construct(BlizzardProfileClient $profileClient)
    {
        $this->profileClient = $profileClient;
    }

    public function getCharacter(string $region, string $realmName, string $characterName)
    {
        $realmName = Str::slug($realmName);
        $characterName = mb_strtolower($characterName);

        $responses = $this->profileClient->getCharacterInfo($region, $realmName, $characterName, true);

        return [
            'name' => $characterName,
            'realm' => $realmName,
            'region' => $region,
            'basic' => $this->mapBasicResponseData($responses['basic']),
            'media' => $this->mapMediaResponseData($responses['media']),
            'equipment' => $this->mapEquipmentResponse($responses['equipment']),
            'specialization' => $this->mapSpecializationResponse($responses['specialization'])
        ];
    }

    private function mapSpecializationResponse(Response $response)
    {
        $data = json_decode($response->getBody());

        // Find active specialization group and spec
        $activeGroup = collect($data->specialization_groups ?? [])
            ->first(fn($group) => $group->is_active);

        if (!$activeGroup) {
            return [
                'activeSpecialization' => null,
                'activeSpecLoadoutCode' => '',
                'classTalents' => [],
                'specTalents' => []
            ];
        }

        $activeSpec = $this->findActiveSpecialization($activeGroup->specializations ?? []);

        return [
            'activeSpecialization' => $activeSpec?->specialization_name,
            'activeSpecLoadoutCode' => $data->_links->self->href ?? '',
            'classTalents' => $this->mapClassTalents($activeGroup->specializations ?? [], $activeSpec?->specialization_name ?? ''),
            'specTalents' => $this->mapSpecTalents($activeSpec?->talents ?? [])
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
            ->filter(fn($spec) => $spec->specialization_name !== $activeSpecName)
            ->flatMap(fn($spec) => $this->mapTalents($spec->talents ?? []))
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
            ->map(fn($talent) => [
                'id' => $talent->talent->id ?? null,
                'spellTooltip' => $talent->spell_tooltip->spell->id ?? null,
                'rank' => $talent->talent_rank ?? null
            ])
            ->all();
    }

    private function mapBasicResponseData(Response $response)
    {
        $data = json_decode($response->getBody());

        $result = [
            'gender' => $data->gender->name,
            'faction' => $data->faction->name,
            'race' => $data->race->id,
            'class' => $data->character_class->id,
            'level' => $data->level,
            'average_item_level' => $data->average_item_level,
            'equipped_item_level' => $data->equipped_item_level,
        ];

        if (isset($data->guild)) {
            $result['guild'] = [
                'name' => $data->guild->name,
                'realm' => $data->guild->realm->name,
                'faction' => $data->guild->faction->name ?? null
            ];
        }

        return $result;
    }

    private function mapMediaResponseData(Response $response)
    {
        $data = json_decode($response->getBody());

        $pictures = [
            'avatar' => $data->avatar_url ?? null,
            'inset' => $data->bust_url ?? null,
            'main' => $data->render_url ?? null
        ];

        if (isset($data->assets)) {
            foreach ($data->assets as $asset) {
                $pictures[$asset->key] = $asset->value;
            }
        }

        return $pictures;
    }

    private function mapEquipmentResponse(Response $response)
    {
        $data = json_decode($response->getBody());

        return array_map(fn($item) => $this->transformItem($item), $data->equipped_items);
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

    private function mapSetItems($set)
    {
        if (!isset($set))
            return null;

        $equippedSetItems = array_filter($set->items, fn($setItem) => isset($setItem->is_equipped));

        return array_values(array_map(fn($set) => $set->item->id, $equippedSetItems));
    }

    private function mapEnchantments($enchantments)
    {
        return array_map(fn($enchant) => $enchant->enchantment_id, $enchantments);
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
