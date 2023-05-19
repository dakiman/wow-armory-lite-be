<?php

namespace App\Http\Controllers;

use App\Services\CharacterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CharacterController extends Controller
{

    private CharacterService $characterService;

    public function __construct(CharacterService $characterService)
    {
        $this->characterService = $characterService;
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

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
