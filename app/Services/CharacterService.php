<?php


namespace App\Services;

use App\DTO\Character\CharacterBasic;
use App\DTO\Character\Item;
use App\DTO\Character\Media;
use App\DTO\Character\Specialization;
use App\DTO\Character\Talent;
use App\Jobs\RetrieveMythicDungeonData;
use App\Models\Character;
use App\Services\Blizzard\BlizzardProfileClient;
use GuzzleHttp\Psr7\Response;
use Str;

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

//        $data = [];
//        foreach ($responses as $response) {
//            $data[] = json_decode($response->getBody());
//        }

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

        if (isset($data->covenant_progress)) {
            $result['covenant'] = [
                'id' => $data->covenant_progress->chosen_covenant->id,
                'name' => $data->covenant_progress->chosen_covenant->name,
                'renown' => $data->covenant_progress->renown_level,
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

        return array_map(fn($equipped) => [
            'id' => $equipped->item->id,
            'itemLevel' => $equipped->level->value,
            'quality' => $equipped->quality->name,
            'slot' => $equipped->slot->name
        ], $data->equipped_items);
    }

    private function mapSpecializationsResponseData(Response $response)
    {
        $data = json_decode($response->getBody());

        $activeSpecName = $data->active_specialization->name;

        $activeSpec = collect($data->specializations)
            ->firstWhere('specialization.name', $activeSpecName);

        $talents = [];
        if (!empty($activeSpec->talents)) {

            $talents = array_map(fn($talent) => new Talent([
                'id' => $talent->spell_tooltip->spell->id,
                'row' => $talent->tier_index ?? null,
                'column' => $talent->column_index ?? null
            ]), $activeSpec->talents);

        }

        return [
            'activeSpecialization' => $activeSpecName,
            'talents' => $talents
        ];
    }

}
