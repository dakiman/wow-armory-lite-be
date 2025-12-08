<?php

namespace App\Jobs;

use App\Models\Guild;
use App\Services\Blizzard\BlizzardProfileClient;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class FetchGuildDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        public Guild $guild
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(BlizzardProfileClient $profileClient): void
    {
        $this->guild->markAsFetching();

        $responses = $profileClient->getGuildInfo(
            $this->guild->region,
            $this->guild->realm,
            $this->guild->name,
            $this->guild->is_classic
        );

        $data = [
            'name' => $this->guild->name,
            'realm' => $this->guild->realm,
            'region' => $this->guild->region,
            'basic' => $this->mapBasicData($responses['basic']),
            'roster' => $this->mapRosterData($responses['roster']),
        ];

        $this->guild->markAsComplete($data);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        $this->guild->markAsFailed();
    }

    /**
     * Map basic guild data from API response.
     */
    private function mapBasicData(Response $response): array
    {
        $basicData = json_decode($response->getBody());

        return [
            'achievement_points' => $basicData->achievement_points,
            'member_count' => $basicData->member_count,
            'created_timestamp' => $basicData->created_timestamp,
            'faction' => $basicData->faction->name,
        ];
    }

    /**
     * Map guild roster data from API response.
     */
    private function mapRosterData(Response $response): array
    {
        $roster = json_decode($response->getBody());

        return collect($roster->members)->map(function ($member) {
            $character = $member->character;

            return [
                'name' => $character->name,
                'realm' => $character->realm->slug,
                'level' => $character->level,
                'class' => $character->playable_class->id,
                'race' => $character->playable_race->id,
                'rank' => $member->rank,
            ];
        })->toArray();
    }
}
