<?php

namespace App\Http\Controllers;

use App\Services\CharacterService;
use App\Services\DungeonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CharacterController extends Controller
{

    private CharacterService $characterService;
    private DungeonService $dungeonService;

    public function __construct(CharacterService $characterService, DungeonService $dungeonService)
    {
        $this->characterService = $characterService;
        $this->dungeonService = $dungeonService;
    }

    /**
     * Display a listing of the resource.
     */
    public function character(string $region, string $realm, string $characterName)
    {
        $character = $this->characterService->getCharacter($region, $realm, $characterName);
//        $character = Cache::remember("$characterName-$realm-$region", 10, function () use ($region, $realm, $characterName) {
//           return $this->characterService->getCharacter($region, $realm, $characterName);
//        });

        return response()->json([
            'character' => $character
        ]);
    }

    public function mythics(string $region, string $realm, string $characterName)
    {
        $mythicData = $this->dungeonService->getCharacterMythicData($region, $realm, $characterName);

        return response()->json([
            'mythic_data' => $mythicData
        ]);
    }
}
