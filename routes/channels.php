<?php

use App\Models\Game;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Aqui voce pode registrar todos os canais de broadcasting da aplicacao.
| Os callbacks de autorizacao verificam se o usuario autenticado pode
| ouvir o canal.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Canal privado da partida.
 * Autoriza acesso apenas ao owner ou participantes (tabela game_players).
 * O $user vem do middleware auth:sanctum (Bearer token).
 */
Broadcast::channel('game.{gameId}', function ($user, $gameId) {
    $player = $user->player;

    if (!$player) {
        return false;
    }

    $game = Game::find($gameId);

    if (!$game) {
        return false;
    }

    // Acesso ao owner da partida
    if ($game->owner_player_id === $player->id) {
        return true;
    }

    // Acesso a participantes da partida (tabela game_players)
    return $game->players()->where('player_id', $player->id)->exists();
});
