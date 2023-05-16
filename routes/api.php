<?php

use App\Http\Controllers\CharacterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/wow', function (\App\Services\CharacterService $characterService) {
    return $characterService->getCharacter("eu", "the maelstrom", "spyroman");
});


Route::get('/wow2', function () {
    return "hi";
});

Route::get('/character/{region}/{realm}/{characterName}', [CharacterController::class, 'character']);

