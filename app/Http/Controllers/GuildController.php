<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetGuildRequest;
use App\Http\Responses\PendingResponse;
use App\Services\GuildService;
use Illuminate\Http\JsonResponse;

class GuildController extends Controller
{
    public function __construct(
        private GuildService $guildService
    ) {
    }

    /**
     * Get guild information.
     */
    public function guild(GetGuildRequest $request): JsonResponse|PendingResponse
    {
        $region = $request->validated()['region'];
        $realm = $request->validated()['realm'];
        $guildName = $request->validated()['guild'];

        $result = $this->guildService->getGuild($region, $realm, $guildName);

        if ($result instanceof PendingResponse) {
            return $result;
        }

        return response()->json([
            'status' => 'complete',
            'guild' => $result,
        ]);
    }

    /**
     * Get popular guilds.
     */
    public function popular(): JsonResponse
    {
        $data = $this->guildService->getPopular();

        return response()->json($data);
    }

    /**
     * Get guild fetch status for polling.
     */
    public function status(int $id): JsonResponse
    {
        $guild = $this->guildService->getGuildById($id);

        if (! $guild) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Guild not found',
            ], 404);
        }

        if ($guild->fetch_status === 'complete' && $guild->data !== null) {
            return response()->json([
                'status' => 'complete',
                'guild' => $guild->data,
            ]);
        }

        if ($guild->fetch_status === 'failed') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to fetch guild data. Please try again.',
            ], 500);
        }

        return response()->json([
            'status' => $guild->fetch_status,
            'message' => $this->getStatusMessage($guild->fetch_status),
        ]);
    }

    /**
     * Get human-readable status message.
     */
    private function getStatusMessage(string $status): string
    {
        return match ($status) {
            'pending' => 'Your request is queued and will be processed shortly.',
            'fetching' => 'Fetching guild data from Blizzard...',
            'idle' => 'No fetch in progress.',
            default => 'Processing...',
        };
    }
}
