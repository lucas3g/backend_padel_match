<?php

namespace App\Listeners;

use App\Events\PlayerLeftGame;
use App\Jobs\SendPushNotification;
use App\Models\Game;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyPlayersOnGameLeave implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(PlayerLeftGame $event): void
    {
        // O jogador já foi detachado pelo LeaveGameAction antes do evento ser disparado,
        // portanto não aparecerá em $game->players. A exclusão abaixo é defensiva.
        $game = Game::with(['owner.user', 'players.user'])->find($event->gameId);

        if (!$game) {
            return;
        }

        $leavingPlayerId = $event->player['id'];
        $playerName      = $event->player['full_name'];

        $users = collect();

        if ($game->owner?->user && $game->owner_player_id !== $leavingPlayerId) {
            $users->push($game->owner->user);
        }

        foreach ($game->players as $player) {
            if ($player->id !== $leavingPlayerId && $player->user) {
                $users->push($player->user);
            }
        }

        $users->unique('id')->each(fn ($user) => SendPushNotification::dispatch(
            $user,
            'Jogador saiu da partida',
            "{$playerName} saiu da partida.",
            ['type' => 'player_left', 'game_id' => (string) $game->id]
        ));
    }
}
