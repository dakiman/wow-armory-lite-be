<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetCharacterRequest;
use App\Http\Responses\PendingResponse;
use App\Services\CharacterService;
use App\Services\ClassicCharacterService;
use App\Services\ProgressionService;
use Illuminate\Http\JsonResponse;

class CharacterController extends Controller
{
    public function __construct(
        private CharacterService $characterService,
        private ProgressionService $progressionService,
        private ClassicCharacterService $classicCharacterService
    ) {
    }

    /**
     * Get character profile information.
     */
    public function character(GetCharacterRequest $request): JsonResponse|PendingResponse
    {
        $region = $request->validated()['region'];
        $realm = $request->validated()['realm'];
        $characterName = $request->validated()['characterName'];
        $isClassic = $request->validated()['isClassic'] ?? false;

        $result = $isClassic
            ? $this->classicCharacterService->getCharacter($region, $realm, $characterName)
            : $this->characterService->getCharacter($region, $realm, $characterName, $isClassic);

        if ($result instanceof PendingResponse) {
            return $result;
        }

        return response()->json([
            'status' => 'complete',
            'character' => $result,
        ]);
    }

    /**
     * Get character mythic keystone progression data.
     */
    public function mythics(GetCharacterRequest $request): JsonResponse
    {
        $region = $request->validated()['region'];
        $realm = $request->validated()['realm'];
        $characterName = $request->validated()['characterName'];

        $mythicsData = $this->progressionService->getCharacterMythics($region, $realm, $characterName);

        return response()->json($mythicsData);
    }

    /**
     * Get character raiding progression data.
     */
    public function raids(GetCharacterRequest $request): JsonResponse
    {
        $region = $request->validated()['region'];
        $realm = $request->validated()['realm'];
        $characterName = $request->validated()['characterName'];

        $raidsData = $this->progressionService->getCharacterRaidingInfo($region, $realm, $characterName);

        return response()->json($raidsData);
    }

    /**
     * Get popular characters.
     */
    public function popular(): JsonResponse
    {
        $data = $this->characterService->getPopular();

        return response()->json($data);
    }

    /**
     * Get character fetch status for polling.
     */
    public function status(int $id): JsonResponse
    {
        $character = $this->characterService->getCharacterById($id);

        if (! $character) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Character not found',
            ], 404);
        }

        if ($character->fetch_status === 'complete' && $character->data !== null) {
            return response()->json([
                'status' => 'complete',
                'character' => $character->data,
            ]);
        }

        if ($character->fetch_status === 'failed') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to fetch character data. Please try again.',
            ], 500);
        }

        return response()->json([
            'status' => $character->fetch_status,
            'message' => $this->getStatusMessage($character->fetch_status),
        ]);
    }

    /**
     * Get human-readable status message.
     */
    private function getStatusMessage(string $status): string
    {
        return match ($status) {
            'pending' => 'Your request is queued and will be processed shortly.',
            'fetching' => 'Fetching character data from Blizzard...',
            'idle' => 'No fetch in progress.',
            default => 'Processing...',
        };
    }
}
