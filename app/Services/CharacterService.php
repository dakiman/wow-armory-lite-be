<?php

namespace App\Services;

use App\Exceptions\RateLimitException;
use App\Http\Responses\PendingResponse;
use App\Jobs\FetchCharacterDataJob;
use App\Models\Character;
use App\Services\Blizzard\BlizzardProfileClient;
use App\Services\Contracts\CharacterServiceInterface;
use App\Services\Traits\MapsCharacterData;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;

class CharacterService implements CharacterServiceInterface
{
    use MapsCharacterData;

    public function __construct(
        private BlizzardProfileClient $profileClient
    ) {
    }

    /**
     * Get character profile data.
     *
     * Returns character data immediately if available and fresh,
     * or queues a background job if rate limited.
     *
     * @return array<string, mixed>|PendingResponse
     */
    public function getCharacter(string $region, string $realmName, string $characterName, bool $isClassic = false): array|PendingResponse
    {
        $realmName = Str::slug($realmName);
        $characterName = mb_strtolower($characterName);

        // Find or create the character record
        $character = Character::findOrCreateByIdentifiers($region, $realmName, $characterName, $isClassic);

        // Track the search
        $character->recordSearch();

        // If we have fresh data, return it immediately
        if ($character->hasFreshData()) {
            return $character->data;
        }

        // Try to fetch data synchronously
        try {
            $data = $this->fetchAndMapData($region, $realmName, $characterName, $isClassic);
            $character->markAsComplete($data);

            return $data;
        } catch (RateLimitException $e) {
            // Rate limited - queue a background job
            if (! $character->isFetchInProgress()) {
                $character->markAsPending();
                FetchCharacterDataJob::dispatch($character);
            }

            // If we have stale data, return it with a note
            if ($character->data !== null) {
                return $character->data;
            }

            return new PendingResponse(
                entity: $character,
                estimatedWait: $e->getRetryAfter() ?? 30
            );
        }
    }

    /**
     * Get popular characters.
     *
     * @return array{most_searched: array, recently_searched: array}
     */
    public function getPopular(): array
    {
        $limit = config('blizzard.popular_limit', 5);

        return [
            'most_searched' => Character::mostSearched($limit)
                ->get()
                ->map(fn (Character $c) => $c->getSummary())
                ->toArray(),
            'recently_searched' => Character::recentlySearched($limit)
                ->get()
                ->map(fn (Character $c) => $c->getSummary())
                ->toArray(),
        ];
    }

    /**
     * Get character by ID for status polling.
     */
    public function getCharacterById(int $id): ?Character
    {
        return Character::find($id);
    }

    /**
     * Fetch and map character data from Blizzard API.
     *
     * @return array<string, mixed>
     */
    private function fetchAndMapData(string $region, string $realmName, string $characterName, bool $isClassic): array
    {
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
