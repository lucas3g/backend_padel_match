<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\PlayerController;
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

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/player', [PlayerController::class, 'show']);
    Route::post('/player', [PlayerController::class, 'store']);
    Route::put('/player', [PlayerController::class, 'update']);

    Route::get('/court', [CourtController::class, 'show']);
    Route::post('/court', [CourtController::class, 'store']);
    Route::put('/court/{id}', [CourtController::class, 'update']);

    Route::get('/game', [GameController::class, 'show']);
    Route::post('/game', [GameController::class, 'store']);
    Route::put('/game/{id}', [GameController::class, 'update']);

});
