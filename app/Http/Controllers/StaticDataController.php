<?php

namespace App\Http\Controllers;

use App\Services\StaticBlizzardDataService;

class StaticDataController extends Controller
{
    private StaticBlizzardDataService $staticBlizzardDataService;

    public function __construct(StaticBlizzardDataService $staticBlizzardDataService)
    {
        $this->staticBlizzardDataService = $staticBlizzardDataService;
    }

    public function realms()
    {
        return response()->json(
            $this->staticBlizzardDataService->getAllRealms()
        );
    }
}
