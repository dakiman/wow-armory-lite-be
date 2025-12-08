<?php

namespace App\Jobs;

use App\Models\Character;
use App\Services\Blizzard\BlizzardProfileClient;
use App\Services\Traits\MapsCharacterData;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class FetchCharacterDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, MapsCharacterData, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Character $character
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(BlizzardProfileClient $profileClient): void
    {
        $this->character->markAsFetching();

        $responses = $profileClient->getCharacterInfo(
            $this->character->region,
            $this->character->realm,
            $this->character->name,
            $this->character->is_classic
        );

        $data = [
            'name' => $this->character->name,
            'realm' => $this->character->realm,
            'region' => $this->character->region,
            'basic' => $this->mapBasicResponseData($responses['basic'], true),
            'media' => $this->mapMediaResponseData($responses['media']),
            'equipment' => $this->mapEquipmentResponseData($responses['equipment']),
            'specialization' => $this->mapSpecializationsResponseData($responses['specialization']),
        ];

        $this->character->markAsComplete($data);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        $this->character->markAsFailed();
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
                'set' => $this->mapSetItems($equipped->set ?? null),
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
     * Map talent specialization data.
     */
    private function mapSpec(mixed $talents): array
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
