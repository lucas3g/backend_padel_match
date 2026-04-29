<?php

namespace App\Actions\Games;

use App\Events\PlayerJoinedGame;
use App\Exceptions\Games\GameIsFullException;
use App\Exceptions\Games\PlayerLevelTooHighException;
use App\Exceptions\Games\PlayerLevelTooLowException;
use App\Exceptions\Games\TeamIsFullException;
use App\Models\Game;
use App\Models\Player;

class JoinGameAction
{
    public function execute(Game $game, Player $player, ?int $team = null): void
    {
        if ($game->max_players && $game->players()->count() >= $game->max_players) {
            throw new GameIsFullException();
        }

        if ($game->min_level && $player->level < $game->min_level) {
            throw new PlayerLevelTooLowException();
        }

        if ($game->max_level && $player->level > $game->max_level) {
            throw new PlayerLevelTooHighException();
        }

        if ($team !== null && $game->players()->wherePivot('team', $team)->count() >= 2) {
            throw new TeamIsFullException();
        }

        $game->players()->syncWithoutDetaching([
            $player->id => ['joined_at' => now(), 'team' => $team],
        ]);

        event(new PlayerJoinedGame($game, $player));

        if ($game->max_players && $game->players()->count() >= $game->max_players) {
            $game->update(['status' => 'full']);
        }
    }
}
