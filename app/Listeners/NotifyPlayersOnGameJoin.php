<?php

namespace App\Listeners;

use App\Events\PlayerJoinedGame;
use App\Jobs\SendPushNotification;
use App\Models\Game;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyPlayersOnGameJoin implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(PlayerJoinedGame $event): void
    {
        // PlayerJoinedGame é ShouldBroadcast e armazena dados como arrays primitivos,
        // por isso recarregamos o Game do banco para obter os modelos com relações.
        $game = Game::with(['owner.user', 'players.user'])->find($event->gameId);

        if (!$game) {
            return;
        }

        $joiningPlayerId = $event->player['id'];
        $playerName      = $event->player['full_name'];

        $users = collect();

        if ($game->owner?->user && $game->owner_player_id !== $joiningPlayerId) {
            $users->push($game->owner->user);
        }

        foreach ($game->players as $player) {
            if ($player->id !== $joiningPlayerId && $player->user) {
                $users->push($player->user);
            }
        }

        $users->unique('id')->each(fn ($user) => SendPushNotification::dispatch(
            $user,
            'Novo jogador na partida!',
            "{$playerName} entrou na partida.",
            ['type' => 'player_joined', 'game_id' => (string) $game->id]
        ));
    }
}
