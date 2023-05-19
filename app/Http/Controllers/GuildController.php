<?php

namespace App\Http\Controllers;

use App\Services\GuildService;
use Illuminate\Http\Request;

class GuildController extends Controller
{
    private GuildService $guildService;

    public function __construct(GuildService $guildService)
    {
        $this->guildService = $guildService;
    }

    public function guild(string $region, string $realm, string $guildName)
    {
        $guild = $this->guildService->getGuild($region, $realm, $guildName);

        return response()->json([
            'guild' => $guild
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
