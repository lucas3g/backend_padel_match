<?php

namespace App\Actions\Games;

use App\Events\PlayerJoinedGame;
use App\Exceptions\Games\GameIsFullException;
use App\Models\Game;
use App\Models\Player;

class JoinGameAction
{
    public function execute(Game $game, Player $player): void
    {
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
