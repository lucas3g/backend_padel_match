<?php

namespace App\Events;

use App\Models\Game;
use App\Models\Player;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerLeftGame implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $gameId;
    public array $player;
    public int $currentPlayersCount;

    /**
     * Evento disparado quando um player sai de uma partida.
     * Enviado via canal privado para todos os participantes restantes.
     */
    public function __construct(Game $game, Player $player)
    {
        $this->gameId = $game->id;
        $this->player = [
            'id' => $player->id,
            'full_name' => $player->full_name,
        ];
        $this->currentPlayersCount = $game->players()->count();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game.' . $this->gameId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'player.left';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->gameId,
            'player' => $this->player,
            'current_players_count' => $this->currentPlayersCount,
        ];
    }
}
