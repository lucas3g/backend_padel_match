<?php

namespace App\Filament\Painel\Widgets;

use Filament\Widgets\Widget;

class RecentResultsWidget extends Widget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.painel.widgets.recent-results-widget';

    protected function getViewData(): array
    {
        $player = auth()->user()?->player;

        if (! $player) {
            return ['results' => []];
        }

        $ultimosJogos = $player->games()
            ->where('games.status', 'completed')
            ->withPivot('team')
            ->orderByDesc('data_time')
            ->limit(5)
            ->get(['games.id', 'games.winner_team', 'games.data_time', 'games.game_type']);

        $results = $ultimosJogos->map(function ($game) {
            $meuTime    = $game->pivot->team;
            $winnerTeam = $game->winner_team;

            if (! $meuTime || ! $winnerTeam) {
                $resultado = 'sem_resultado';
            } else {
                $resultado = ((int) $meuTime === (int) $winnerTeam) ? 'vitoria' : 'derrota';
            }

            return [
                'game_id'   => $game->id,
                'data_time' => $game->data_time,
                'game_type' => $game->game_type,
                'resultado' => $resultado,
            ];
        })->values()->toArray();

        return ['results' => $results];
    }
}
