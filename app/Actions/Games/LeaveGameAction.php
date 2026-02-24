<?php

namespace App\Actions\Games;

use App\Events\PlayerLeftGame;
use App\Models\Game;
use App\Models\GameInvitation;
use App\Models\Player;

class LeaveGameAction
{
    public function execute(Game $game, Player $player): void
    {
        $game->players()->detach($player->id);

        GameInvitation::where('game_id', $game->id)
            ->where('player_id', $player->id)
            ->delete();

        event(new PlayerLeftGame($game, $player));

        if ($game->status === 'full') {
            $game->update(['status' => 'open']);
        }
    }
}
