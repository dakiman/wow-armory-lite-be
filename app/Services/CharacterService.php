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

        $character = [
            'name' => $characterName,
            'realm' => $realmName,
            'region' => $region,
            'basic' => $this->mapBasicResponseData($responses['basic']),
            'media' => $this->mapMediaResponseData($responses['media']),
            'equipment' => $this->mapEquipmentResponseData($responses['equipment']),
            'specialization' => $this->mapSpecializationsResponseData($responses['specialization']),
        ];

        return $character;
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

        $pictures = [];

        if (isset($data->assets)) {
            foreach ($data->assets as $asset) {
                $pictures[$asset->key] = $asset->value;
            }
        } else {
            $pictures = [
                'avatar' => $data->avatar_url,
                'inset' => $data->bust_url,
                'main' => $data->render_url
            ];
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
//        dd($data);

        $activeSpecName = $data->active_specialization->name;

        $activeSpec = collect($data->specializations)
            ->firstWhere('specialization.name', $activeSpecName);

        $loadout = collect($activeSpec->loadouts)
            ->firstWhere('is_active', true);

//        echo(json_encode($loadout->selected_class_talents));
        $classTalents = collect($loadout->selected_class_talents)->map(function ($talent) {
            return [
                'id' => $talent->tooltip->talent->id,
                'spellTooltip' => $talent->tooltip->spell_tooltip->spell->id,
                'rank' => $talent->rank
            ];
        });

        $specTalents = collect($loadout->selected_spec_talents)->map(function ($talent) {
            return [
                'id' => $talent->tooltip->talent->id,
                'spellTooltip' => $talent->tooltip->spell_tooltip->spell->id,
                'rank' => $talent->rank,
            ];
        });

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
