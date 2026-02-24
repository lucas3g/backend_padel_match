<?php

namespace App\Actions\Games;

use App\Events\PlayerJoinedGame;
use App\Exceptions\Games\GameIsFullException;
use App\Exceptions\Games\GameNotOpenException;
use App\Models\GameInvitation;
use App\Models\Player;

class AcceptGameInvitationAction
{
    public function execute(GameInvitation $invitation, Player $player): void
    {
        $invitation->update(['status' => 'accepted']);

        $game = $invitation->game;

        if ($game->status !== 'open') {
            throw new GameNotOpenException();
        }

        if ($game->players()->where('players.id', $player->id)->exists()) {
            return;
        }

        if ($game->max_players && $game->players()->count() >= $game->max_players) {
            throw new GameIsFullException();
        }

        $game->players()->syncWithoutDetaching([
            $player->id => ['joined_at' => now()],
        ]);

        event(new PlayerJoinedGame($game, $player));

        if ($game->max_players && $game->players()->count() >= $game->max_players) {
            $game->update(['status' => 'full']);
        }
    }
}
