<?php

namespace App\Services\Traits;

use GuzzleHttp\Psr7\Response;

trait MapsCharacterData
{
    /**
     * Map basic character response data.
     *
     * @param  bool  $includeAchievementPoints Whether to include achievement points (retail only)
     */
    protected function mapBasicResponseData(Response $response, bool $includeAchievementPoints = false): array
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

        if ($includeAchievementPoints && isset($data->achievement_points)) {
            $result['achievement_points'] = $data->achievement_points;
        }

        if (isset($data->guild)) {
            $result['guild'] = [
                'name' => $data->guild->name,
                'realm' => $data->guild->realm->name,
                'faction' => $data->guild->faction->name ?? null,
            ];
        }

        return $result;
    }

    /**
     * Map media response data.
     */
    protected function mapMediaResponseData(Response $response): array
    {
        $data = json_decode($response->getBody());

        $pictures = [
            'avatar' => $data->avatar_url ?? null,
            'inset' => $data->bust_url ?? null,
            'main' => $data->render_url ?? null,
        ];

        if (isset($data->assets)) {
            foreach ($data->assets as $asset) {
                $pictures[$asset->key] = $asset->value;
            }
        }

        return $pictures;
    }

    /**
     * Map set items from equipment data.
     *
     * @param  mixed  $set
     */
    protected function mapSetItems($set): ?array
    {
        if (! isset($set)) {
            return null;
        }

        $equippedSetItems = array_filter($set->items, fn ($setItem) => isset($setItem->is_equipped));

        return array_values(array_map(fn ($set) => $set->item->id, $equippedSetItems));
    }

    /**
     * Map enchantments from equipment data.
     */
    protected function mapEnchantments(array $enchantments): ?array
    {
        if (empty($enchantments)) {
            return null;
        }

        return array_map(fn ($enchant) => $enchant->enchantment_id, $enchantments);
    }
}
