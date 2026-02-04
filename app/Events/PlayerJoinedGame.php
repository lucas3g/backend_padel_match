<?php

namespace App\Events;

use App\Models\Game;
use App\Models\Player;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerJoinedGame implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $gameId;
    public array $player;
    public int $currentPlayersCount;
    public int $maxPlayers;

    /**
     * Evento disparado quando um player entra em uma partida.
     * Enviado via canal privado para todos os participantes da partida.
     */
    public function __construct(Game $game, Player $player)
    {
        $this->gameId = $game->id;
        $this->player = [
            'id' => $player->id,
            'full_name' => $player->full_name,
            'level' => $player->level,
            'side' => $player->side,
        ];
        $this->currentPlayersCount = $game->players()->count();
        $this->maxPlayers = $game->max_players;
    }

    /**
     * Canal privado da partida.
     * Apenas participantes autorizados recebem o evento.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game.' . $this->gameId),
        ];
    }

    /**
     * Nome limpo do evento para o cliente (Flutter).
     * Recebido como 'player.joined' no Pusher client.
     */
    public function broadcastAs(): string
    {
        return 'player.joined';
    }

    /**
     * Payload enviado ao cliente.
     * Apenas campos necessarios para a UI atualizar.
     */
    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->gameId,
            'player' => $this->player,
            'current_players_count' => $this->currentPlayersCount,
            'max_players' => $this->maxPlayers,
        ];
    }
}
