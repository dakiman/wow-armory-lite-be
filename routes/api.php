<?php

use App\Http\Controllers\CharacterController;
use App\Http\Controllers\GuildController;
use App\Http\Controllers\StaticDataController;
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

// Character routes
Route::get('/character/popular', [CharacterController::class, 'popular'])->name('character.popular');
Route::get('/character/status/{id}', [CharacterController::class, 'status'])->name('character.status');
Route::get('/character/{region}/{realm}/{characterName}', [CharacterController::class, 'character']);
Route::get('/character/mythics/{region}/{realm}/{characterName}', [CharacterController::class, 'mythics']);
Route::get('/character/raids/{region}/{realm}/{characterName}', [CharacterController::class, 'raids']);

// Guild routes
Route::get('/guild/popular', [GuildController::class, 'popular'])->name('guild.popular');
Route::get('/guild/status/{id}', [GuildController::class, 'status'])->name('guild.status');
Route::get('/guild/{region}/{realm}/{guild}', [GuildController::class, 'guild']);

// Static data routes
Route::get('/realms', [StaticDataController::class, 'realms']);
