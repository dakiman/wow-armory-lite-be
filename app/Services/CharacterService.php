<?php


namespace App\Services;

use App\Services\Blizzard\BlizzardProfileClient;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;

class CharacterService
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

        $responses = $this->profileClient->getCharacterInfo($region, $realmName, $characterName);

        return [
            'name' => $characterName,
            'realm' => $realmName,
            'region' => $region,
            'basic' => $this->mapBasicResponseData($responses['basic']),
            'media' => $this->mapMediaResponseData($responses['media']),
            'equipment' => $this->mapEquipmentResponseData($responses['equipment']),
            'specialization' => $this->mapSpecializationsResponseData($responses['specialization']),
        ];
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
            'achievement_points' => $data->achievement_points,
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

    private function mapEquipmentResponseData(Response $response)
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
                'enchantments' => $this->mapEnchantments($equipped)
            ];
        }, $data->equipped_items);
    }

    private function mapSpecializationsResponseData(Response $response)
    {
        $data = json_decode($response->getBody());

        $activeSpecName = $data->active_specialization->name;

        $activeSpec = current(array_filter($data->specializations, function ($specialization) use ($activeSpecName) {
            return $specialization->specialization->name === $activeSpecName;
        }));

        $loadout = current(array_filter($activeSpec->loadouts, function ($loadout) {
            return $loadout->is_active;
        }));

        $classTalents = array_map(function ($talent) {
            return [
                'id' => $talent->tooltip->talent->id,
                'spellTooltip' => $talent->tooltip->spell_tooltip->spell->id,
                'rank' => $talent->rank
            ];
        }, $loadout->selected_class_talents);

        $specTalents = array_map(function ($talent) {
            return [
                'id' => $talent->tooltip->talent->id,
                'spellTooltip' => $talent->tooltip->spell_tooltip->spell->id,
                'rank' => $talent->rank,
            ];
        }, $loadout->selected_spec_talents);

        return [
            'activeSpecialization' => $activeSpecName,
            'activeSpecLoadoutCode' => $loadout->talent_loadout_code,
            'classTalents' => $classTalents,
            'specTalents' => $specTalents
        ];
    }

    private function mapSockets($item)
    {
        if (!isset($item->sockets) || !empty($item->sockets))
            return null;

        return array_map(fn($socket) => $socket->item->id, $item->sockets);
    }

    private function mapSet($item)
    {
        if (!isset($item->set))
            return null;

        $equippedSetItems = array_filter($item->set->items, fn($set) => isset($set->is_equipped));

        return array_values(array_map(fn($set) => $set->item->id, $equippedSetItems));
    }

    private function mapEnchantments($item)
    {
        if(!isset($item->enchantments))
            return null;

        return array_map(fn($enchantment) => $enchantment->enchantment_id, $item->enchantments);
    }

}
