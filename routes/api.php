<?php

use App\Http\Controllers\AccountDeletionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\ClubCourtController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\PlayerFavoriteClubController;
use App\Http\Controllers\PlayerFavoritePlayerController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameFinalizationController;
use App\Http\Controllers\GameInvitationController;
use App\Http\Controllers\MunicipioController;
use App\Http\Controllers\PlayerSuggestionController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\RankingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─── Público ─────────────────────────────────────────────────────────────────

Route::post('/login',             [AuthController::class, 'login']);
Route::post('/register',          [AuthController::class, 'register']);
Route::post('/password/forgot',   [AuthController::class, 'forgotPassword']);
Route::post('/password/reset',    [AuthController::class, 'resetPassword']);

// ─── Autenticado (sem exigir e-mail verificado) ───────────────────────────────
// Logout, verificação e reenvio devem funcionar mesmo sem e-mail confirmado.

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout',         [AuthController::class, 'logout']);
    Route::post('/email/verify',   [AuthController::class, 'verifyEmail']);
    Route::post('/email/resend',   [AuthController::class, 'resendVerification']);
    Route::put('/user/fcm-token',  [AuthController::class, 'updateFcmToken']);
    Route::delete('/account',      [AccountDeletionController::class, 'destroyApi']);
});

// ─── Autenticado + e-mail verificado ─────────────────────────────────────────

Route::middleware(['auth:sanctum', 'verified.api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/players', [PlayerController::class, 'index']);
    Route::get('/players/suggest', [PlayerSuggestionController::class, 'standalone']);
    Route::get('/player/{player}/perfil', [PlayerController::class, 'perfil']);
    Route::get('/player/{player}/partidas', [PlayerController::class, 'partidas']);
    Route::get('/player/{id?}', [PlayerController::class, 'show']);
    Route::post('/player', [PlayerController::class, 'store']);
    Route::put('/player', [PlayerController::class, 'update']);
    Route::get('me/player', [PlayerController::class, 'me']);
    Route::patch('me/player/disponibilidade', [PlayerController::class, 'definirDisponibilidade']);
    Route::get('me/player/clubes-favoritos', [PlayerFavoriteClubController::class, 'index']);
    Route::put('me/player/clubes-favoritos', [PlayerFavoriteClubController::class, 'sync']);
    Route::post('me/player/clubes-favoritos/{club}', [PlayerFavoriteClubController::class, 'store']);
    Route::delete('me/player/clubes-favoritos/{club}', [PlayerFavoriteClubController::class, 'destroy']);
    Route::get('me/player/jogadores-favoritos', [PlayerFavoritePlayerController::class, 'index']);
    Route::put('me/player/jogadores-favoritos', [PlayerFavoritePlayerController::class, 'sync']);
    Route::post('me/player/jogadores-favoritos/{player}', [PlayerFavoritePlayerController::class, 'store']);
    Route::delete('me/player/jogadores-favoritos/{player}', [PlayerFavoritePlayerController::class, 'destroy']);

    Route::get('/municipios', [MunicipioController::class, 'index']);

    Route::get('/clubs', [ClubController::class, 'index']);
    Route::get('/club/{id}', [ClubController::class, 'show']);
    Route::get('/court/{id?}', [CourtController::class, 'show']);
    Route::get('/club/{club}/courts', [ClubCourtController::class, 'index']);

    Route::middleware('role:admin|club_manager')->group(function () {
        Route::post('/club', [ClubController::class, 'store']);
        Route::put('/club/{id}', [ClubController::class, 'update']);
        Route::post('/court', [CourtController::class, 'store']);
        Route::put('/court/{id}', [CourtController::class, 'update']);
    });

    Route::get('/game', [GameController::class, 'index']);
    Route::get('/game/available', [GameController::class, 'available']);
    Route::get('/game/invitations', [GameInvitationController::class, 'myInvitations']);
    Route::post('/game', [GameController::class, 'store']);

    Route::get('/game/{game}/teams', [GameController::class, 'teams']);
    Route::get('/game/{game}/sets', [GameFinalizationController::class, 'sets']);
    Route::get('/game/{game}/suggest-players', [PlayerSuggestionController::class, 'forGame']);
    Route::post('/game/{game}/finalize', [GameFinalizationController::class, 'finalize']);
    Route::post('/game/{game}/join', [GameController::class, 'join']);
    Route::post('/game/{game}/leave', [GameController::class, 'leave']);
    Route::delete('/game/{game}/players/{player}', [GameController::class, 'removePlayer']);
    Route::put('/game/{game}/players/{player}/team', [GameController::class, 'assignTeam']);

    Route::get('/game/{id}', [GameController::class, 'show']);
    Route::put('/game/{id}', [GameController::class, 'update']);

    Route::post('/game/invitation/{invitation}/accept', [GameInvitationController::class, 'accept']);
    Route::post('/game/invitation/{invitation}/reject', [GameInvitationController::class, 'reject']);
    Route::post('/game/{game}/invite/{player}', [GameInvitationController::class, 'invite']);
    Route::delete('/game/{game}/invite/{player}', [GameInvitationController::class, 'cancelInvite']);

    Route::prefix('ranking')->group(function () {
        Route::get('/players',              [RankingController::class, 'players']);
        Route::get('/players/{player}',     [RankingController::class, 'playerCard']);
        Route::get('/clubs',                [RankingController::class, 'clubs']);
        Route::get('/clubs/{club}/players', [RankingController::class, 'clubPlayers']);
        Route::get('/clubs/{club}',         [RankingController::class, 'clubCard']);
    });

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
