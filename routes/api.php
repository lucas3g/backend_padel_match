<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\ClubCourtController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameInvitationController;

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
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/players', [PlayerController::class, 'index']);
    Route::get('/player/{id?}', [PlayerController::class, 'show']);
    Route::post('/player', [PlayerController::class, 'store']);
    Route::put('/player', [PlayerController::class, 'update']);
    Route::get('me/player', [PlayerController::class, 'me']);

    Route::get('/clubs', [ClubController::class, 'index']);
    Route::get('/club/{id}', [ClubController::class, 'show']);
    Route::post('/club', [ClubController::class, 'store']);
    Route::put('/club/{id}', [ClubController::class, 'update']);

    Route::get('/court/{id?}', [CourtController::class, 'show']);
    Route::post('/court', [CourtController::class, 'store']);
    Route::put('/court/{id}', [CourtController::class, 'update']);

    Route::get('/club/{club}/courts', [ClubCourtController::class, 'index']);
    
    Route::get('/game', [GameController::class, 'index']);
    Route::get('/game/available', [GameController::class, 'available']);
    Route::get('/game/{id}', [GameController::class, 'show']);
    Route::post('/game', [GameController::class, 'store']);
    Route::put('/game/{id}', [GameController::class, 'update']);
    Route::post('/game/{game}/join', [GameController::class, 'join']);
    Route::post('/game/{game}/leave', [GameController::class, 'leave']);

    // Convites de partidas - rotas estáticas antes das dinâmicas
    Route::get('/game/invitations', [GameInvitationController::class, 'myInvitations']);
    Route::post('/game/invitation/{invitation}/accept', [GameInvitationController::class, 'accept']);
    Route::post('/game/invitation/{invitation}/reject', [GameInvitationController::class, 'reject']);
    Route::post('/game/{game}/invite/{player}', [GameInvitationController::class, 'invite']);
    Route::delete('/game/{game}/invite/{player}', [GameInvitationController::class, 'cancelInvite']);

    // Amigos - rotas estáticas primeiro para evitar conflito com parâmetros dinâmicos
    Route::get('/friends', [FriendController::class, 'index']);
    Route::get('/friends/pending', [FriendController::class, 'pending']);
    Route::get('/friends/sent', [FriendController::class, 'sent']);
    Route::get('/friends/favorites', [FriendController::class, 'favorites']);
    Route::post('/friends/request/{player}', [FriendController::class, 'sendRequest']);
    Route::post('/friends/{friend}/accept', [FriendController::class, 'accept']);
    Route::post('/friends/{friend}/reject', [FriendController::class, 'reject']);
    Route::delete('/friends/{player}', [FriendController::class, 'remove']);
    Route::post('/friends/{player}/block', [FriendController::class, 'block']);
    Route::post('/friends/{player}/favorite', [FriendController::class, 'toggleFavorite']);
    Route::delete('/friends/{player}/favorite', [FriendController::class, 'removeFavorite']);
});
