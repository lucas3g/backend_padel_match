<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $gameId;
    public array $team1;
    public array $team2;
    public array $unassigned;

    public function __construct(Game $game)
    {
        $this->gameId = $game->id;

        $players = $game->players()
            ->get(['players.id', 'players.full_name', 'players.level', 'players.side', 'players.profile_image_url']);

        $format = fn ($col) => $col->map(fn ($p) => [
            'id'                => $p->id,
            'full_name'         => $p->full_name,
            'level'             => $p->level,
            'side'              => $p->side,
            'profile_image_url' => $p->profile_image_url,
        ])->values()->all();

        $this->team1      = $format($players->filter(fn ($p) => (int) $p->pivot->team === 1));
        $this->team2      = $format($players->filter(fn ($p) => (int) $p->pivot->team === 2));
        $this->unassigned = $format($players->filter(fn ($p) => $p->pivot->team === null));
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('game.' . $this->gameId)];
    }

    public function broadcastAs(): string
    {
        return 'teams.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id'    => $this->gameId,
            'team1'      => $this->team1,
            'team2'      => $this->team2,
            'unassigned' => $this->unassigned,
        ];
    }
}
