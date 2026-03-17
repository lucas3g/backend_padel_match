<?php

namespace App\Listeners;

use App\Events\GameFinalized;
use App\Jobs\SendPushNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyPlayersOnGameFinalized implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(GameFinalized $event): void
    {
        $game = $event->game;
        $game->loadMissing(['players.user', 'owner.user']);

        $users = collect();

        foreach ($game->players as $player) {
            if ($player->user) {
                $users->push($player->user);
            }
        }

        // Inclui o dono mesmo que não esteja na lista de players (caso de borda)
        if ($game->owner?->user) {
            $users->push($game->owner->user);
        }

        $gameTitle = $game->title ?? 'Partida';

        $users->unique('id')->each(fn ($user) => SendPushNotification::dispatch(
            $user,
            'Partida finalizada!',
            "A partida \"{$gameTitle}\" foi finalizada. Confira o resultado!",
            ['type' => 'game_finalized', 'game_id' => (string) $game->id]
        ));
    }
}
