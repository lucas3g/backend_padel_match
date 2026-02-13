<?php

namespace App\Policies;

use App\Models\Game;
use App\Models\User;

class GamePolicy
{
    /**
     * Visualizar partida.
     * Publica: qualquer player autenticado.
     * Privada: owner, convidado (pending/accepted) ou ja participa.
     */
    public function view(User $user, Game $game): bool
    {
        if ($game->type === 'public') {
            return true;
        }

        $player = $user->player;

        if (!$player) {
            return false;
        }

        return $game->canAccess($player);
    }

    /**
     * Entrar na partida.
     * Publica + open: qualquer player.
     * Privada + open: owner ou convidado (accepted).
     */
    public function join(User $user, Game $game): bool
    {
        $player = $user->player;

        if (!$player) {
            return false;
        }

        if ($game->status !== 'open') {
            return false;
        }

        if ($game->type === 'public') {
            return true;
        }

        return $game->isOwner($player) || $game->isInvitedAccepted($player);
    }

    /**
     * Convidar jogadores.
     * Somente o owner de partida privada.
     */
    public function invite(User $user, Game $game): bool
    {
        $player = $user->player;

        if (!$player) {
            return false;
        }

        return $game->type === 'private' && $game->isOwner($player);
    }

    /**
     * Atualizar partida.
     * Somente o owner.
     */
    public function update(User $user, Game $game): bool
    {
        return $user->player && $user->player->id === $game->owner_player_id;
    }
}
