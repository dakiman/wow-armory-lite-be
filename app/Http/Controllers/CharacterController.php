<?php

namespace App\Http\Controllers;

use App\Services\CharacterService;
use App\Services\ClassicCharacterService;
use App\Services\ProgressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CharacterController extends Controller
{

    private CharacterService $characterService;
    private ClassicCharacterService $classicCharacterService;
    private ProgressionService $progressionService;

    public function __construct(CharacterService $characterService, ProgressionService $dungeonService, ClassicCharacterService $classicCharacterService)
    {
        $this->characterService = $characterService;
        $this->progressionService = $dungeonService;
        $this->classicCharacterService = $classicCharacterService;
    }

    /**
     * Display a listing of the resource.
     */
    public function character(string $region, string $realm, string $characterName)
    {
        $isClassic = request()->query('isClassic', false);
//        $character = $this->characterService->getCharacter($region, $realm, $characterName);
        $character = Cache::remember("$characterName-$realm-$region-profile-$isClassic", 3600, function () use ($region, $realm, $characterName, $isClassic) {
            return $isClassic ?
                $this->classicCharacterService->getCharacter($region, $realm, $characterName) :
                $this->characterService->getCharacter($region, $realm, $characterName);
        });

        return response()->json([
            'character' => $character
        ]);
    }

    public function mythics(string $region, string $realm, string $characterName)
    {
//        $mythicsData = $this->progressionService->getCharacterMythics($region, $realm, $characterName);

        $mythicsData = Cache::remember("$characterName-$realm-$region-mythics", 3600, function () use ($region, $realm, $characterName) {
            return $this->progressionService->getCharacterMythics($region, $realm, $characterName);
        });

        return response()->json(
            $mythicsData
        );
    }

    public function raids(string $region, string $realm, string $characterName)
    {
//        $raidsData = $this->progressionService->getCharacterRaidingInfo($region, $realm, $characterName);

        $raidsData = Cache::remember("$characterName-$realm-$region-raids", 3600, function () use ($region, $realm, $characterName) {
            return $this->progressionService->getCharacterRaidingInfo($region, $realm, $characterName);
        });

        return response()->json(
            $raidsData
        );
    }
}
