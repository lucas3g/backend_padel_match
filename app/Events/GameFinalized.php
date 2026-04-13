<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameFinalized implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int    $gameId;
    public string $gameTitle;
    public int    $team1Score;
    public int    $team2Score;
    public ?int   $winnerTeam;

    public function __construct(public readonly Game $game)
    {
        $this->gameId     = $game->id;
        $this->gameTitle  = $game->title ?? 'Partida';
        $this->team1Score = (int) $game->team1_score;
        $this->team2Score = (int) $game->team2_score;
        $this->winnerTeam = $game->winner_team;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('game.' . $this->gameId)];
    }

    public function broadcastAs(): string
    {
        return 'game.finalized';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id'     => $this->gameId,
            'title'       => $this->gameTitle,
            'team1_score' => $this->team1Score,
            'team2_score' => $this->team2Score,
            'winner_team' => $this->winnerTeam,
            'status'      => 'completed',
        ];
    }
}
