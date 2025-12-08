<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetGuildRequest;
use App\Services\GuildService;

class GuildController extends Controller
{
    private GuildService $guildService;

    public function __construct(GuildService $guildService)
    {
        $this->guildService = $guildService;
    }

    /**
     * Get guild information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function guild(GetGuildRequest $request)
    {
        $region = $request->validated()['region'];
        $realm = $request->validated()['realm'];
        $guildName = $request->validated()['guild'];

        $guild = $this->guildService->getGuild($region, $realm, $guildName);

        return response()->json([
            'guild' => $guild,
        ]);
    }
}
