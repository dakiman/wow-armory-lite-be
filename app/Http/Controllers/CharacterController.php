<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetCharacterRequest;
use App\Services\CharacterService;
use App\Services\ClassicCharacterService;
use App\Services\ProgressionService;

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
     * Get character profile information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function character(GetCharacterRequest $request)
    {
        $region = $request->validated()['region'];
        $realm = $request->validated()['realm'];
        $characterName = $request->validated()['characterName'];
        $isClassic = $request->validated()['isClassic'] ?? false;

        $character = $isClassic ?
            $this->classicCharacterService->getCharacter($region, $realm, $characterName) :
            $this->characterService->getCharacter($region, $realm, $characterName, $isClassic);

        return response()->json([
            'character' => $character,
        ]);
    }

    /**
     * Get character mythic keystone progression data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mythics(GetCharacterRequest $request)
    {
        $region = $request->validated()['region'];
        $realm = $request->validated()['realm'];
        $characterName = $request->validated()['characterName'];

        $mythicsData = $this->progressionService->getCharacterMythics($region, $realm, $characterName);

        return response()->json($mythicsData);
    }

    /**
     * Get character raiding progression data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function raids(GetCharacterRequest $request)
    {
        $region = $request->validated()['region'];
        $realm = $request->validated()['realm'];
        $characterName = $request->validated()['characterName'];

        $raidsData = $this->progressionService->getCharacterRaidingInfo($region, $realm, $characterName);

        return response()->json($raidsData);
    }
}
